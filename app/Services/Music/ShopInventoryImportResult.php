<?php

declare(strict_types=1);

namespace App\Services\Music;

final class ShopInventoryImportResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public array $errors = [],
    ) {}

    public function addError(int $rowNumber, string $message): void
    {
        $this->errors[] = __('ui.music.shop_import_row_error', ['row' => $rowNumber, 'message' => $message]);
    }
}
