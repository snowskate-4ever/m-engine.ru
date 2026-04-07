<?php

declare(strict_types=1);

namespace Tests\Feature\Messenger;

use App\Enums\ConversationType;
use App\Enums\MessageKind;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\Messenger\MessengerRetentionPruner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessengerRetentionPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_messages_older_than_retention_window(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'type' => ConversationType::Group,
            'title' => 'G',
            'created_by_user_id' => $user->id,
            'retention_days' => 7,
        ]);

        $stale = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'kind' => MessageKind::Text,
            'body' => 'stale',
        ]);
        $stale->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        $fresh = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'kind' => MessageKind::Text,
            'body' => 'fresh',
        ]);
        $fresh->forceFill(['created_at' => now()->subDays(1)])->saveQuietly();

        Artisan::call('messenger:prune-retention');

        $this->assertDatabaseMissing('messages', ['id' => $stale->id]);
        $this->assertDatabaseHas('messages', ['id' => $fresh->id]);
    }

    public function test_skips_conversations_without_retention(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'type' => ConversationType::Group,
            'title' => 'Forever',
            'created_by_user_id' => $user->id,
            'retention_days' => null,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'kind' => MessageKind::Text,
            'body' => 'old',
        ]);
        $message->forceFill(['created_at' => now()->subDays(365)])->saveQuietly();

        Artisan::call('messenger:prune-retention');

        $this->assertDatabaseHas('messages', ['id' => $message->id]);
    }

    public function test_removes_attachment_files_from_disk(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $conversation = Conversation::create([
            'type' => ConversationType::Group,
            'title' => 'G',
            'created_by_user_id' => $user->id,
            'retention_days' => 7,
        ]);

        $path = 'messenger/attachments/test.bin';
        Storage::disk('local')->put($path, 'binary');

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'kind' => MessageKind::File,
            'body' => null,
        ]);
        $message->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        MessageAttachment::create([
            'message_id' => $message->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'test.bin',
            'mime' => 'application/octet-stream',
            'size' => 6,
        ]);

        Artisan::call('messenger:prune-retention');

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_dry_run_does_not_delete(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'type' => ConversationType::Group,
            'title' => 'G',
            'created_by_user_id' => $user->id,
            'retention_days' => 7,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'kind' => MessageKind::Text,
            'body' => 'x',
        ]);
        $message->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        $stats = app(MessengerRetentionPruner::class)->prune(dryRun: true);

        $this->assertSame(1, $stats['messages_deleted']);
        $this->assertDatabaseHas('messages', ['id' => $message->id]);
    }
}
