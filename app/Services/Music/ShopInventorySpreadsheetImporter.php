<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\ShopItemCondition;
use App\Models\Good;
use App\Models\Shop;
use App\Models\ShopItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ShopInventorySpreadsheetImporter
{
    private const MAX_ROWS = 5000;

    /**
     * @param  array<string, string>  $headerToField  key: normalized header, value: field name
     */
    private const HEADER_ALIASES = [
        'code' => 'code',
        'sku' => 'code',
        'article' => 'code',
        'артикул' => 'code',
        'price' => 'price',
        'цена' => 'price',
        'stock' => 'stock_quantity',
        'stock_quantity' => 'stock_quantity',
        'qty' => 'stock_quantity',
        'quantity' => 'stock_quantity',
        'остаток' => 'stock_quantity',
        'condition' => 'condition',
        'состояние' => 'condition',
        'good_code' => 'good_code',
        'catalog_code' => 'good_code',
        'good_sku' => 'good_code',
    ];

    public function import(Shop $shop, string $absolutePath): ShopInventoryImportResult
    {
        $result = new ShopInventoryImportResult;

        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $raw = $sheet->toArray(null, true, true, false);

        if ($raw === [] || ! isset($raw[0])) {
            $result->addError(1, __('ui.music.shop_import_empty'));

            return $result;
        }

        $headerRow = $raw[0];
        $colMap = $this->mapHeaderRow($headerRow);
        if (! isset($colMap['code'])) {
            $result->addError(1, __('ui.music.shop_import_need_code_column'));

            return $result;
        }

        $goodByCodeCache = [];

        for ($i = 1; $i < count($raw); $i++) {
            $rowNum = $i + 1;
            if ($i > self::MAX_ROWS) {
                $result->addError($rowNum, __('ui.music.shop_import_max_rows', ['max' => self::MAX_ROWS]));
                break;
            }

            $row = $raw[$i];
            $code = $this->cell($row, $colMap['code'] ?? null);
            if ($code === null || trim((string) $code) === '') {
                $result->skipped++;

                continue;
            }
            $code = trim((string) $code);

            $priceRaw = $this->cell($row, $colMap['price'] ?? null);
            $stockRaw = $this->cell($row, $colMap['stock_quantity'] ?? null);
            $conditionRaw = $this->cell($row, $colMap['condition'] ?? null);
            $goodCodeRaw = $this->cell($row, $colMap['good_code'] ?? null);

            $price = $this->parseDecimal($priceRaw, $rowNum, $result);
            if ($price === null && $priceRaw !== null && trim((string) $priceRaw) !== '') {
                continue;
            }
            $price ??= '0';

            $stock = $this->parseInt($stockRaw, $rowNum, $result);
            if ($stock === null && $stockRaw !== null && trim((string) $stockRaw) !== '') {
                continue;
            }
            $stock ??= 0;

            $condition = $this->parseCondition($conditionRaw);
            if ($condition === null && $conditionRaw !== null && trim((string) $conditionRaw) !== '') {
                $result->addError($rowNum, __('ui.music.shop_import_bad_condition'));

                continue;
            }
            $condition ??= ShopItemCondition::New;

            $goodId = null;
            if ($goodCodeRaw !== null && trim((string) $goodCodeRaw) !== '') {
                $gCode = trim((string) $goodCodeRaw);
                if (! isset($goodByCodeCache[$gCode])) {
                    $goodByCodeCache[$gCode] = Good::query()->where('code', $gCode)->value('id');
                }
                $goodId = $goodByCodeCache[$gCode];
                if ($goodId === null) {
                    $result->addError($rowNum, __('ui.music.shop_import_unknown_good_code', ['code' => $gCode]));

                    continue;
                }
            } else {
                if (! isset($goodByCodeCache[$code])) {
                    $goodByCodeCache[$code] = Good::query()->where('code', $code)->value('id');
                }
                $goodId = $goodByCodeCache[$code];
                if ($goodId === null) {
                    $result->addError($rowNum, __('ui.music.shop_import_need_good', ['code' => $code]));

                    continue;
                }
            }

            $existing = ShopItem::query()
                ->where('shop_id', $shop->id)
                ->where('code', $code)
                ->first();

            $payload = [
                'shop_id' => $shop->id,
                'code' => $code,
                'price' => $price,
                'stock_quantity' => $stock,
                'condition' => $condition,
                'good_id' => $goodId,
            ];

            if ($existing !== null) {
                $existing->update($payload);
                if ($condition === ShopItemCondition::New) {
                    $existing->images()->get()->each->delete();
                }
                $result->updated++;
            } else {
                ShopItem::query()->create($payload + [
                    'title_override' => null,
                    'description_override' => null,
                ]);
                $result->created++;
            }
        }

        return $result;
    }

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $ss = new Spreadsheet;
        $sh = $ss->getActiveSheet();
        $sh->fromArray([
            ['code', 'price', 'stock_quantity', 'condition', 'good_code'],
            ['SHOP-SKU-1', '100.00', '2', 'new', 'GOOD-CODE-1'],
            ['SHOP-SKU-2', '50', '0', 'used', 'GOOD-CODE-1'],
        ]);

        return $ss;
    }

    public function templateXlsxBytes(): string
    {
        $ss = $this->buildTemplateSpreadsheet();
        $writer = new Xlsx($ss);
        ob_start();
        $writer->save('php://output');
        $bin = ob_get_clean();

        return $bin !== false ? $bin : '';
    }

    private function mapHeaderRow(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colIndex => $cell) {
            if ($cell === null || $cell === '') {
                continue;
            }
            $norm = mb_strtolower(trim((string) $cell));
            $norm = preg_replace('/\s+/u', '_', $norm) ?? $norm;
            if (isset(self::HEADER_ALIASES[$norm])) {
                $field = self::HEADER_ALIASES[$norm];
                $map[$field] = $colIndex;
            }
        }

        return $map;
    }

    private function cell(array $row, ?int $colIndex): mixed
    {
        if ($colIndex === null) {
            return null;
        }

        return $row[$colIndex] ?? null;
    }

    private function parseDecimal(mixed $raw, int $rowNum, ShopInventoryImportResult $result): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return number_format((float) $raw, 2, '.', '');
        }
        $s = str_replace(',', '.', trim((string) $raw));
        if (is_numeric($s)) {
            return number_format((float) $s, 2, '.', '');
        }
        $result->addError($rowNum, __('ui.music.shop_import_bad_price'));

        return null;
    }

    private function parseInt(mixed $raw, int $rowNum, ShopInventoryImportResult $result): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return max(0, (int) $raw);
        }
        $result->addError($rowNum, __('ui.music.shop_import_bad_stock'));

        return null;
    }

    private function parseCondition(mixed $raw): ?ShopItemCondition
    {
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }
        $v = mb_strtolower(trim((string) $raw));
        if (in_array($v, ['new', 'n', 'новый', 'нов', 'новое'], true)) {
            return ShopItemCondition::New;
        }
        if (in_array($v, ['used', 'u', 'б/у', 'бy', 'second'], true)) {
            return ShopItemCondition::Used;
        }

        return null;
    }
}
