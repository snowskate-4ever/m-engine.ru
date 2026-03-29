<?php

declare(strict_types=1);

namespace Tests\Feature\Messenger;

use App\Enums\MessageKind;
use App\Events\Messenger\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessengerBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_message_dispatches_message_sent_event(): void
    {
        Event::fake([MessageSent::class]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'hello ws',
        ])->assertStatus(201);

        Event::assertDispatched(MessageSent::class, function (MessageSent $e) use ($cid) {
            return $e->message->conversation_id === $cid
                && $e->message->body === 'hello ws';
        });
    }

    public function test_idempotent_resend_does_not_dispatch_message_sent_again(): void
    {
        Event::fake([MessageSent::class]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $uuid = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22';
        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'once',
            'client_message_id' => $uuid,
        ])->assertStatus(201);

        Event::assertDispatchedTimes(MessageSent::class, 1);

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'retry',
            'client_message_id' => $uuid,
        ])->assertStatus(201);

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_message_sent_broadcast_immediate_flag(): void
    {
        $message = new Message([
            'conversation_id' => 1,
            'user_id' => 1,
            'kind' => MessageKind::Text,
            'body' => 'x',
            'is_forward' => false,
        ]);
        $message->id = 1;

        $this->assertFalse((new MessageSent($message))->shouldBroadcastNow());
        $this->assertTrue((new MessageSent($message, true))->shouldBroadcastNow());
    }
}
