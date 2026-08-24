<?php

declare(strict_types=1);

namespace App\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Stream a CSV download.
     *
     * @param  array<int,string>  $headers
     * @param  iterable<int|string,array<string,mixed>|object>  $rows
     */
    public function stream(array $headers, iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                if (is_object($row)) {
                    $row = (array) $row;
                }
                fputcsv($out, array_map(
                    fn ($v): string => is_array($v) ? json_encode($v) : (string) $v,
                    $row,
                ));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
