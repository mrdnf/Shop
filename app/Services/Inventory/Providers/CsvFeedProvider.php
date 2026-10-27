<?php
declare(strict_types=1);

namespace App\Services\Inventory\Providers;

use App\Core\Http;
use App\Services\Inventory\AbstractProvider;

/** CSV feed over HTTP — e.g. a nightly ERP export or a published Google Sheet. */
final class CsvFeedProvider extends AbstractProvider
{
    public static function label(): string
    {
        return 'CSV feed';
    }

    public static function description(): string
    {
        return 'Read a CSV file published by your ERP, FTP gateway or Google Sheets (File → Publish → CSV).';
    }

    public static function fields(): array
    {
        return array_merge([
            ['name' => 'url', 'label' => 'CSV URL', 'type' => 'text', 'placeholder' => 'https://docs.google.com/spreadsheets/d/.../pub?output=csv', 'required' => true],
            ['name' => 'delimiter', 'label' => 'Delimiter', 'type' => 'select', 'options' => [',' => 'Comma (,)', ';' => 'Semicolon (;)', 'tab' => 'Tab', '|' => 'Pipe (|)'], 'default' => ','],
            ['name' => 'sku_column', 'label' => 'SKU column', 'type' => 'text', 'default' => 'sku', 'help' => 'Header name, or 1-based column number.'],
            ['name' => 'qty_column', 'label' => 'Quantity column', 'type' => 'text', 'default' => 'quantity'],
        ], self::authFields());
    }

    public function fetchAll(): array
    {
        $res = Http::request('GET', (string) $this->cfg('url', ''), Http::authHeaders($this->config), null, 60);
        if ($res['status'] >= 400) {
            throw new \RuntimeException("CSV feed returned HTTP {$res['status']}");
        }
        $delimiter = $this->cfg('delimiter', ',');
        $delimiter = $delimiter === 'tab' ? "\t" : (string) $delimiter;

        $rows = array_map(
            static fn ($line) => str_getcsv($line, $delimiter, '"', '\\'),
            preg_split('/\r\n|\n|\r/', trim(preg_replace('/^\xEF\xBB\xBF/', '', $res['body']) ?? '')) ?: []
        );
        if (!$rows) {
            return [];
        }

        $skuCol = (string) $this->cfg('sku_column', 'sku');
        $qtyCol = (string) $this->cfg('qty_column', 'quantity');
        if (ctype_digit($skuCol) && ctype_digit($qtyCol)) {
            $skuIdx = (int) $skuCol - 1;
            $qtyIdx = (int) $qtyCol - 1;
        } else {
            $header = array_map(static fn ($h) => strtolower(trim((string) $h)), array_shift($rows));
            $skuIdx = ctype_digit($skuCol) ? (int) $skuCol - 1 : array_search(strtolower($skuCol), $header, true);
            $qtyIdx = ctype_digit($qtyCol) ? (int) $qtyCol - 1 : array_search(strtolower($qtyCol), $header, true);
            if ($skuIdx === false || $qtyIdx === false) {
                throw new \RuntimeException('SKU or quantity column not found in CSV header.');
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $sku = trim((string) ($row[$skuIdx] ?? ''));
            $qty = $row[$qtyIdx] ?? null;
            if ($sku !== '' && is_numeric($qty)) {
                $out[$sku] = ($out[$sku] ?? 0) + (int) floor((float) $qty);
            }
        }
        return $out;
    }
}
