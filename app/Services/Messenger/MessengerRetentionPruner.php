<?php

declare(strict_types=1);

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class MessengerRetentionPruner
{
    /**
     * Delete messages older than each conversation's sliding retention window (N full days before now).
     *
     * @return array{messages_deleted: int, conversations_touched: int, files_deleted: int, file_delete_failures: int}
     */
    public function prune(bool $dryRun = false): array
    {
        $chunk = max(1, (int) config('messenger.retention_prune_chunk_size', 500));

        $messagesDeleted = 0;
        $conversationsTouched = 0;
        $filesDeleted = 0;
        $fileFailures = 0;

        foreach (Conversation::query()->whereNotNull('retention_days')->cursor() as $conversation) {
            $days = (int) $conversation->retention_days;
            if ($days < 1) {
                continue;
            }

            $cutoff = now()->subDays($days);
            $touchedThisConversation = false;

            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById($chunk, function ($messages) use ($dryRun, &$messagesDeleted, &$touchedThisConversation, &$filesDeleted, &$fileFailures) {
                    $touchedThisConversation = true;
                    $messages->load('attachments');

                    if ($dryRun) {
                        $messagesDeleted += $messages->count();

                        return;
                    }

                    foreach ($messages as $message) {
                        foreach ($message->attachments as $attachment) {
                            try {
                                if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                                    Storage::disk($attachment->disk)->delete($attachment->path);
                                    $filesDeleted++;
                                }
                            } catch (Throwable $e) {
                                $fileFailures++;
                                Log::warning('messenger.retention_prune_file_delete_failed', [
                                    'attachment_id' => $attachment->id,
                                    'disk' => $attachment->disk,
                                    'path' => $attachment->path,
                                    'message' => $e->getMessage(),
                                ]);
                            }
                        }
                    }

                    $ids = $messages->pluck('id')->all();
                    $messagesDeleted += Message::query()->whereIn('id', $ids)->delete();
                });

            if ($touchedThisConversation) {
                $conversationsTouched++;
            }
        }

        return [
            'messages_deleted' => $messagesDeleted,
            'conversations_touched' => $conversationsTouched,
            'files_deleted' => $filesDeleted,
            'file_delete_failures' => $fileFailures,
        ];
    }
}
