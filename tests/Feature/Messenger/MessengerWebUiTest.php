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

        Livewire::test(\App\Livewire\Messenger\MessengerIndex::class)
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
}
