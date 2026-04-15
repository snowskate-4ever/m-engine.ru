<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class AnalyticsCsvExporter
{
    /**
     * @param  list<array<string, scalar|null>>  $rows
     */
    public function streamDownload(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = $row[$h] ?? '';
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
