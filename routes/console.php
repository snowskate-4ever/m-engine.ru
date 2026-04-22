<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Services\Messenger\SupportChatService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('messenger:backfill-support-chats', function () {
    /** @var SupportChatService $support */
    $support = app(SupportChatService::class);
    $count = 0;
    User::query()->orderBy('id')->chunkById(300, function ($users) use ($support, &$count): void {
        foreach ($users as $user) {
            if ($support->ensureForUser($user) !== null) {
                $count++;
            }
        }
    });

    $this->info("Support chats ensured: {$count}");
})->purpose('Ensure support chat exists for every user');
