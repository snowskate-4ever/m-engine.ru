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

    public function test_embedded_messenger_workspace_can_create_direct_chat(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $component = Livewire::actingAs($u1)->test(
            \App\Livewire\Messenger\MessengerWorkspace::class,
            ['embedMode' => true],
        )
            ->assertSet('embedMode', true)
            ->set('createType', 'direct')
            ->set('directUserId', (string) $u2->id)
            ->call('createChat')
            ->assertHasNoErrors();

        $activeId = $component->get('activeConversationId');
        $this->assertNotNull($activeId);
        $this->assertGreaterThan(0, $activeId);
    }
}
