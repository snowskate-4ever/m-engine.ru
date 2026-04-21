<?php

declare(strict_types=1);

namespace Tests\Feature\Messenger;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessengerWebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_messenger_index_redirects_guests(): void
    {
        $this->get(route('messenger.index'))->assertRedirect();
    }

    public function test_messenger_index_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('messenger.index'))
            ->assertOk()
            ->assertSee(__('ui.messenger.chats'));
    }

    public function test_notification_settings_page_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Messenger\MessengerNotificationSettings::class)
            ->assertOk()
            ->assertSee(__('ui.messenger.push_label'));
    }

    public function test_messenger_new_chat_modal_can_create_direct_chat(): void
    {
        $u1 = User::factory()->create(['name' => 'Alpha Unique']);
        $u2 = User::factory()->create(['name' => 'Beta Unique']);

        Livewire::actingAs($u1)->test(\App\Livewire\Messenger\MessengerNewChatModal::class)
            ->call('openModal')
            ->assertSet('open', true)
            ->set('createType', 'direct')
            ->set('peerSearch', 'Beta Unique')
            ->assertSet('directUserId', '')
            ->call('selectPeer', $u2->id)
            ->assertSet('directUserId', (string) $u2->id)
            ->call('createChat')
            ->assertHasNoErrors()
            ->assertSet('open', false);
    }
}
