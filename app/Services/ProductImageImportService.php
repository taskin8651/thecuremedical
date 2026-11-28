<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class ProductImageImportService
{
    public const MAX_ZIP_KB = 128 * 1024;
    public const MAX_ENTRIES = 1000;
    private const MAX_PIXELS = 24_000_000;
    private const MAX_IMAGE_BYTES = 4096 * 1024;
    private const LIFETIME_SECONDS = 86400;
    private const DIRECTORY = 'product-image-imports';
    private const TYPES = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'webp' => IMAGETYPE_WEBP];

    public function stage(UploadedFile $file, int $owner): string
    {
        if (! method_exists(ZipArchive::class, 'getStreamIndex') || ! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['archive' => 'Bulk image upload requires PHP 8.2 or newer with ZIP and GD enabled.']);
        }

        $id = (string) Str::uuid();
        $directory = $this->directory($id);
        File::ensureDirectoryExists($directory, 0700);

        try {
            $file->move($directory, 'archive.zip');
            $zip = $this->openArchive($directory);
            try {
                if ($zip->numFiles > self::MAX_ENTRIES) {
                    throw ValidationException::withMessages(['archive' => 'The ZIP has more than '.self::MAX_ENTRIES.' entries. Split it into smaller ZIPs.']);
                }
                $rows = $this->index($zip);
            } finally {
                $zip->close();
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['archive' => 'The ZIP contains no files to preview.']);
            }

            if (file_put_contents($directory.'/lock', '') === false) {
                throw new RuntimeException('Unable to create the bulk image upload lock.');
            }
            $this->save($directory, ['owner' => $owner, 'phase' => 'checking', 'cursor' => 0, 'rows' => $rows]);
        } catch (Throwable $e) {
            File::deleteDirectory($directory);
            throw $e;
        }

        return $id;
    }

    public function get(string $id, int $owner): array
    {
        return $this->withState($id, $owner, fn ($state) => $state);
    }

    public function advance(string $id, int $owner, string $action): array
    {
        return $this->withState($id, $owner, function (array $state, string $directory) use ($id, $action) {
            if ($action === 'confirm' && $state['phase'] === 'preview') {
                $state['phase'] = 'importing';
                $state['cursor'] = 0;
                $this->save($directory, $state);
            }
            $checking = $action === 'check' && $state['phase'] === 'checking';
            $importing = in_array($action, ['confirm', 'import'], true) && $state['phase'] === 'importing';
            if (! $checking && ! $importing) {
                return $state;
            }

            $zip = $this->openArchive($directory);
            try {
                $deadline = microtime(true) + 3;
                $total = count($state['rows']);
                $processed = 0;
                while ($state['cursor'] < $total) {
                    $index = $state['cursor']++;
                    $row = &$state['rows'][$index];
                    if ($checking && $row['status'] === 'checking') {
                        $row['status'] = $this->checkImage($zip, $row);
                        $processed++;
                    } elseif ($importing && $row['status'] === 'ready') {
                        $row['status'] = $this->importImage($zip, $row, $directory, $id);
                        $processed++;
                    }
                    unset($row);
                    if ($processed >= ($checking ? 12 : 5) || microtime(true) >= $deadline) {
                        break;
                    }
                }
                if ($state['cursor'] === $total) {
                    $state['phase'] = $checking ? 'preview' : 'complete';
                }
                $this->save($directory, $state);
            } finally {
                $zip->close();
            }
            if ($state['phase'] === 'complete') {
                File::delete($directory.'/archive.zip');
            }

            return $state;
        });
    }

    public function prune(): void
    {
        foreach (File::glob($this->directory().'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            if (! Str::isUuid(basename($directory))) {
                continue;
            }
            $lockPath = $directory.'/lock';
            if (! $this->expired($lockPath)) {
                continue;
            }
            $lock = @fopen($lockPath, 'r+b');
            if ($lock === false) {
                if ($this->expired($directory)) {
                    File::deleteDirectory($directory);
                }
                continue;
            }
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                fclose($lock);
                continue;
            }
            File::delete($directory.'/state.json');
            fclose($lock);
            File::deleteDirectory($directory);
        }
    }

    private function index(ZipArchive $zip): array
    {
        $rows = [];
        $counts = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);
            if ($entry === false) {
                throw ValidationException::withMessages(['archive' => 'A ZIP entry could not be read. Recreate the ZIP and try again.']);
            }
            $name = $entry['name'];
            $path = rtrim($name, '/');
            $safePath = strlen($name) <= 1024
                && mb_check_encoding($name, 'UTF-8')
                && ! preg_match('~(^|/)\.{1,2}(/|$)|^/|//|[:\x00-\x1f\x7f]~', $name)
                && ! str_contains($name, '\\');
            $zip->getExternalAttributesIndex($index, $system, $attributes);
            $type = $system === ZipArchive::OPSYS_UNIX ? (($attributes >> 16) & 0170000) : 0;
            if ($safePath && str_ends_with($name, '/') && in_array($type, [0, 0040000], true)) {
                continue;
            }
            $safeFile = $safePath && in_array($type, [0, 0100000], true) && ! str_ends_with($name, '/');
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $sku = $safeFile ? pathinfo($path, PATHINFO_FILENAME) : null;
            $sku = $sku !== '' && $sku !== null && mb_strlen($sku) <= 80 ? $sku : null;
            $candidate = $sku !== null && isset(self::TYPES[$extension]);
            if ($candidate) {
                $counts[$sku] = ($counts[$sku] ?? 0) + 1;
            }
            $status = match (true) {
                ! $safeFile => 'unsafe',
                ! $candidate => 'invalid',
                $entry['size'] > self::MAX_IMAGE_BYTES => 'oversized',
                default => 'checking',
            };
            $rows[] = [
                'index' => $index, 'file' => mb_strimwidth(mb_scrub($name), 0, 200, '…'), 'sku' => $sku,
                'extension' => $extension, 'size' => $entry['size'], 'crc' => sprintf('%08x', $entry['crc']),
                'status' => $status, 'product_id' => null, 'product' => null, 'has_image' => false, 'duplicate' => $candidate,
            ];
        }

        $products = collect();
        $skus = array_unique(array_filter(array_column($rows, 'sku'), fn ($sku) => $sku !== null));
        foreach (array_chunk($skus, 250) as $chunk) {
            $matches = Product::query()->whereIn('sku', $chunk)->select(['id', 'sku', 'name'])
                ->withExists(['media as has_image' => fn ($query) => $query->where('collection_name', 'images')])->get();
            foreach ($matches as $product) {
                $products->put($product->sku, $product);
            }
        }

        foreach ($rows as &$row) {
            $product = $products->get($row['sku']);
            $row['product_id'] = $product?->id;
            $row['product'] = $product?->name;
            $row['has_image'] = (bool) $product?->has_image;
            $row['duplicate'] = $row['duplicate'] && $counts[$row['sku']] > 1;
        }
        unset($row);

        return $rows;
    }

    private function checkImage(ZipArchive $zip, array $row): string
    {
        $bytes = $this->readImage($zip, $row);
        $info = $bytes === null ? false : @getimagesizefromstring($bytes);
        if (! $info || $info[2] !== self::TYPES[$row['extension']]) {
            return 'invalid';
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return 'dimensions';
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return 'invalid';
        }
        unset($image);

        return match (true) {
            $row['duplicate'] => 'duplicate',
            $row['product_id'] === null => 'unmatched',
            $row['has_image'] => 'existing',
            default => 'ready',
        };
    }

    private function readImage(ZipArchive $zip, array $row): ?string
    {
        $stream = @$zip->getStreamIndex($row['index']);
        if ($stream === false) {
            return null;
        }
        try {
            $bytes = @stream_get_contents($stream, self::MAX_IMAGE_BYTES + 1);
        } finally {
            fclose($stream);
        }

        return $bytes !== false && strlen($bytes) <= self::MAX_IMAGE_BYTES
            && strlen($bytes) === $row['size'] && hash('crc32b', $bytes) === $row['crc'] ? $bytes : null;
    }

    private function importImage(ZipArchive $zip, array $row, string $directory, string $id): string
    {
        $temporary = $directory.'/image.tmp';
        try {
            return DB::transaction(function () use ($zip, $row, $temporary, $id) {
                $product = Product::query()->lockForUpdate()->find($row['product_id']);
                if (! $product || $product->sku !== $row['sku']) {
                    return 'changed';
                }
                $images = $product->getMedia('images');
                if ($images->isNotEmpty()) {
                    // A retry can arrive after media committed and before progress was saved.
                    return $images->contains(fn ($media) => $media->getCustomProperty('bulk_image_import') === $id)
                        ? 'uploaded' : 'existing';
                }

                $bytes = $this->readImage($zip, $row);
                if ($bytes === null || file_put_contents($temporary, $bytes) !== strlen($bytes)) {
                    throw new RuntimeException('Unable to read or stage a validated ZIP image.');
                }
                $filename = Str::uuid().'.'.$row['extension'];
                try {
                    $media = $product->addMedia($temporary)->preservingOriginal()
                        ->usingName($row['sku'])->usingFileName($filename)
                        ->withCustomProperties(['primary' => true, 'bulk_image_import' => $id])
                        ->toMediaCollection('images');
                    if (! $media->hasGeneratedConversion('thumb')) {
                        throw new RuntimeException('The product thumbnail could not be generated.');
                    }
                } catch (Throwable $e) {
                    $product->media()->where('file_name', $filename)->first()?->delete();
                    throw $e;
                }

                return 'uploaded';
            });
        } catch (Throwable $e) {
            report($e);

            return 'failed';
        } finally {
            File::delete($temporary);
        }
    }

    private function openArchive(string $directory): ZipArchive
    {
        $zip = new ZipArchive;
        if (@$zip->open($directory.'/archive.zip', ZipArchive::RDONLY) !== true) {
            throw ValidationException::withMessages(['archive' => 'The ZIP could not be opened. Recreate the ZIP and try again.']);
        }

        return $zip;
    }

    private function withState(string $id, int $owner, callable $callback): array
    {
        abort_unless(Str::isUuid($id), 404);
        $directory = $this->directory($id);
        $lock = @fopen($directory.'/lock', 'r+b');
        abort_if($lock === false, 404);
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the bulk image upload.');
            }
            abort_if($this->expired($directory.'/lock') || ! is_file($directory.'/state.json'), 404);
            $state = json_decode(File::get($directory.'/state.json'), true, 512, JSON_THROW_ON_ERROR);
            abort_unless($state['owner'] === $owner, 404);

            return $callback($state, $directory);
        } finally {
            fclose($lock);
        }
    }

    private function save(string $directory, array $state): void
    {
        $json = json_encode($state, JSON_THROW_ON_ERROR);
        if (file_put_contents($directory.'/state.tmp', $json) !== strlen($json)
            || ! rename($directory.'/state.tmp', $directory.'/state.json')) {
            throw new RuntimeException('Unable to save bulk image upload progress.');
        }
    }

    private function expired(string $path): bool
    {
        $modified = @filemtime($path);

        return $modified === false || $modified <= time() - self::LIFETIME_SECONDS;
    }

    private function directory(?string $id = null): string
    {
        return Storage::disk('local')->path(self::DIRECTORY.($id === null ? '' : '/'.$id));
    }
}
