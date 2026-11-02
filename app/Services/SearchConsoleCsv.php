<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SearchConsoleCsv
{
    public function parse(UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'r');
        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            if (! $header) {
                $this->invalid();
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if ($header !== ['Top pages', 'Clicks', 'Impressions', 'CTR', 'Position']) {
                $this->invalid('Upload the English Search Console Performance → Pages CSV with Top pages, Clicks, Impressions, CTR and Position columns.');
            }
            $rows = [];
            $seen = [];
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($rows) >= 5000 || count($row) !== 5) {
                    $this->invalid();
                }
                [$page, $clicks, $impressions, $ctr, $position] = $row;
                if (! filter_var($page, FILTER_VALIDATE_URL) || ! in_array(parse_url($page, PHP_URL_SCHEME), ['https', 'http'], true) || strlen($page) > 2048 || isset($seen[$page]) || ! preg_match('/^\d{1,10}$/D', $clicks) || ! preg_match('/^\d{1,10}$/D', $impressions) || (int) $clicks > (int) $impressions || ! preg_match('/^\d+(?:\.\d+)?%$/D', $ctr) || (float) $ctr > 100 || ! is_numeric($position) || ! is_finite((float) $position) || (float) $position < 0 || (float) $position > 1000) {
                    $this->invalid();
                }
                $seen[$page] = true;
                $rows[] = ['page' => $page, 'clicks' => (int) $clicks, 'impressions' => (int) $impressions, 'position' => (float) $position];
            }
            if (! count($rows)) {
                $this->invalid();
            }
            usort($rows, fn ($a, $b) => $b['clicks'] <=> $a['clicks']);

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function invalid(string $message = 'The CSV contains invalid or duplicate page rows, or exceeds 5,000 rows.'): never
    {
        throw ValidationException::withMessages(['report' => $message]);
    }
}
