<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExporter
{
    /**
     * Stream a CSV download. Cells that start with = + - @ are prefixed with
     * an apostrophe to prevent spreadsheet formula injection.
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'safe'], array_values((array) $row)));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function safe(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        $s = (string) $value;

        return $s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$s : $s;
    }
}
