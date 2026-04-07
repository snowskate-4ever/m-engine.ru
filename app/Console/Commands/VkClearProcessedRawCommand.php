<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\VkPost;
use Illuminate\Console\Command;

class VkClearProcessedRawCommand extends Command
{
    protected $signature = 'vk:clear-processed-raw
                            {--dry-run : Показать, сколько постов будет очищено, без изменений}';

    protected $description = 'Очистить raw_json у постов, у которых заполнен processed_at (освободить место)';

    public function handle(): int
    {
        $query = VkPost::processed()->whereNotNull('raw_json');
        $count = $query->count();

        if ($count === 0) {
            $this->info('Нет обработанных постов с сырыми данными.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Будет очищено raw_json у {$count} постов. Запустите без --dry-run для применения.");
            return self::SUCCESS;
        }

        $query->update(['raw_json' => null]);
        $this->info("Очищено raw_json у {$count} постов.");
        return self::SUCCESS;
    }
}
