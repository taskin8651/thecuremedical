<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Feature\Concerns\Helpers;
use Tests\TestCase;
use ZipArchive;

class ProductImageImportTest extends TestCase
{
    use Helpers, RefreshDatabase;

    public function test_preview_and_import_preserve_product_fields_and_support_reimport(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->staff('Manager'));
        $this->get(route('admin.products.index'))->assertOk()->assertSee('Bulk Image Upload')->assertSee('Import CSV');

        $product = $this->product(['sku' => 'SMASK-003', 'mrp' => null, 'sale_price' => 12, 'stock_quantity' => 8, 'is_verified' => true]);
        $originalAttributes = $product->refresh()->getRawOriginal();
        $duplicate = $this->product(['sku' => 'DUP']);
        $existing = $this->product(['sku' => 'EXISTING']);
        $other = $this->product(['sku' => 'CHANGED']);
        $current = $existing->addMedia(UploadedFile::fake()->image('current.jpg'))->toMediaCollection('images');
        $entries = [
            'photos/SMASK-003.webp' => $this->image('webp'),
            'SMASK-004.jpg' => $this->image('jpeg'),
            'smask-003.png' => $this->image('png'),
            'a/DUP.png' => $this->image('png'),
            'b/DUP.jpg' => $this->image('jpeg'),
            'EXISTING.jpg' => $this->image('jpeg'),
            'CHANGED.png' => $this->image('png'),
            'fake.jpg' => $this->image('png'),
            'broken.png' => 'invalid payload',
            'large.webp' => str_repeat('x', 4194305),
            '../outside.jpg' => $this->image('jpeg'),
        ];

        $url = $this->stage($entries);
        $this->progress($url, 'import', 'checking');
        $this->finish($url, 'check', 'checking', 'preview');
        $this->get($url)->assertOk()->assertSee('Confirm upload')
            ->assertSee($product->name)->assertSee('2 ready to upload')->assertSee('5 skipped')->assertSee('4 failed')
            ->assertSee('Unmatched SKU')->assertSee('Duplicate SKU')->assertSee('Product already has an image')
            ->assertSee('Invalid image')->assertSee('Over 4 MB')->assertSee('Unsafe ZIP entry');
        $this->assertDatabaseCount('media', 1);

        $other->update(['sku' => 'NEW-SKU']);
        $this->post($url, ['action' => 'confirm'])->assertRedirect($url);
        $this->finish($url, 'import', 'importing', 'complete');
        $this->get($url)->assertOk()->assertSee('1 uploaded')->assertSee('6 skipped')->assertSee('4 failed');

        $product->refresh();
        $this->assertSame($originalAttributes, $product->getRawOriginal());
        $this->assertNull($product->mrp);
        $media = $product->primaryImage();
        $this->assertTrue($media->getCustomProperty('primary'));
        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot('thumb'));
        $this->assertSame($current->id, $existing->fresh()->primaryImage()->id);
        $this->assertFalse($duplicate->fresh()->hasMedia('images'));
        $this->get(route('site.products'))->assertOk()->assertSee($media->getUrl('thumb'));
        $this->get(route('site.product', $product->slug))->assertOk()->assertSee('id="mainImg" src="'.$media->getUrl().'"', false);
        Storage::disk('local')->assertMissing('product-image-imports/'.basename($url).'/archive.zip');

        $this->progress($url, 'confirm', 'complete');
        $secondUrl = $this->stage(['SMASK-003.webp' => $entries['photos/SMASK-003.webp']]);
        $this->finish($secondUrl, 'check', 'checking', 'preview');
        $this->get($secondUrl)->assertSee('Product already has an image');
        $this->progress($secondUrl, 'confirm', 'complete');
        $this->get($secondUrl)->assertSee('0 uploaded')->assertSee('1 skipped')->assertSee('0 failed');
        $this->assertDatabaseCount('media', 2);
    }

    public function test_import_uses_batches_and_rechecks_images_added_after_preview(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->staff('Manager'));
        $entries = [];
        $images = ['jpg' => $this->image('jpeg'), 'png' => $this->image('png')];
        for ($i = 0; $i < 14; $i++) {
            $this->product(['sku' => 'SKU-'.$i]);
            $extension = $i % 2 === 0 ? 'jpg' : 'png';
            $entries['SKU-'.$i.'.'.$extension] = $images[$extension];
        }
        $url = $this->stage($entries);
        $this->progress($url, 'check', 'checking');
        $this->finish($url, 'check', 'checking', 'preview');
        $this->get($url)->assertSee('14 ready to upload');

        $existing = Product::where('sku', 'SKU-0')->firstOrFail();
        $existing->addMedia(UploadedFile::fake()->image('other.png'))->toMediaCollection('images');
        $this->progress($url, 'confirm', 'importing');
        $this->assertLessThanOrEqual(6, Media::count());
        $this->finish($url, 'import', 'importing', 'complete');
        $this->get($url)->assertSee('13 uploaded')->assertSee('1 skipped')->assertSee('0 failed');
        $this->assertCount(1, $existing->fresh()->getMedia('images'));
        $this->assertDatabaseCount('media', 14);
    }

    public function test_media_failure_cleans_up_and_the_next_file_imports(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->staff('Manager'));
        $failed = $this->product(['sku' => 'FAIL']);
        $success = $this->product(['sku' => 'PASS']);
        $image = $this->image('png');
        $url = $this->stage(['FAIL.png' => $image, 'PASS.png' => $image]);
        $this->finish($url, 'check', 'checking', 'preview');

        $failedFile = null;
        Event::listen(MediaHasBeenAdded::class, function (MediaHasBeenAdded $event) use (&$failedFile) {
            if ($event->media->name === 'FAIL') {
                $failedFile = $event->media->getPathRelativeToRoot();
                throw new RuntimeException('Simulated media write failure.');
            }
        });
        $this->postJson($url, ['action' => 'confirm'])->assertOk();
        $this->finish($url, 'import', 'importing', 'complete');
        $this->get($url)->assertSee('1 uploaded')->assertSee('0 skipped')->assertSee('1 failed')->assertSee('Could not save image');
        $this->assertNotNull($failedFile);
        Storage::disk('public')->assertMissing($failedFile);
        $this->assertFalse($failed->fresh()->hasMedia('images'));
        $this->assertTrue($success->fresh()->hasMedia('images'));
    }

    public function test_batch_actions_require_existing_permissions_and_ownership(): void
    {
        Storage::fake('local');
        $manager = $this->staff('Manager');
        $pharmacist = $this->staff('Pharmacist');
        $this->actingAs($manager);
        $url = $this->stage(['X.png' => $this->image('png')]);

        $this->actingAs($pharmacist);
        $this->get(route('admin.products.index'))->assertOk()->assertDontSee('Bulk Image Upload');
        $this->get(route('admin.products.image-import.create'))->assertForbidden();
        $this->postJson($url, ['action' => 'confirm'])->assertForbidden();

        $otherManager = User::query()->create(['name' => 'Second manager', 'email' => 'other@example.test', 'password' => bcrypt('x'), 'is_active' => true]);
        $otherManager->assignRole('Manager');
        $this->actingAs($otherManager);
        $this->get($url)->assertNotFound();
        $this->postJson($url, ['action' => 'check'])->assertNotFound();
    }

    public function test_csv_import_still_uses_the_existing_form_and_validation(): void
    {
        $this->actingAs($this->staff('Manager'));
        $this->get(route('admin.products.import'))->assertOk()->assertSee('Import products');
        $csv = UploadedFile::fake()->createWithContent('products.csv', "sku,mrp\nCSV-1,50\n")->mimeType('text/csv');
        $this->post(route('admin.products.import.store'), ['file' => $csv])
            ->assertSessionHasErrors(['file' => 'Missing required column "name".']);
    }

    private function image(string $type): string
    {
        $image = imagecreatetruecolor(16, 16);
        ob_start();
        match ($type) {
            'jpeg' => imagejpeg($image),
            'png' => imagepng($image),
            'webp' => imagewebp($image),
        };

        return ob_get_clean();
    }

    private function stage(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'product-images');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::OVERWRITE));
        foreach ($entries as $name => $bytes) {
            $zip->addFromString($name, $bytes);
        }
        $this->assertTrue($zip->close());

        $response = $this->post(route('admin.products.image-import.store'), [
            'archive' => new UploadedFile($path, 'photos.zip', null, null, true),
        ])->assertRedirect()->assertSessionHasNoErrors();

        return $response->headers->get('Location');
    }

    private function progress(string $url, string $action, string $expected): void
    {
        $this->postJson($url, ['action' => $action])->assertOk()->assertJsonPath('phase', $expected);
    }

    private function finish(string $url, string $action, string $pending, string $finished): void
    {
        for ($i = 0; $i < 20; $i++) {
            $response = $this->postJson($url, ['action' => $action])->assertOk();
            if ($response->json('phase') === $finished) {
                return;
            }
            $response->assertJsonPath('phase', $pending);
        }
        $this->fail('Import did not reach '.$finished);
    }
}
