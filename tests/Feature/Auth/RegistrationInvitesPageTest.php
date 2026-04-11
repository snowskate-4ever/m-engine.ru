<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\RegistrationInvite;
use App\Models\User;
use App\Services\Auth\RegistrationInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationInvitesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_invites_page_requires_authentication(): void
    {
        $response = $this->get(route('settings.registration-invites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_invites(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        RegistrationInvite::query()->create([
            'created_by_user_id' => (int) $owner->id,
            'token_hash' => hash('sha256', 'owner-token'),
            'token_encrypted' => encrypt('owner-token'),
            'is_active' => true,
        ]);

        RegistrationInvite::query()->create([
            'created_by_user_id' => (int) $other->id,
            'token_hash' => hash('sha256', 'other-token'),
            'token_encrypted' => encrypt('other-token'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('settings.registration-invites.index'));

        $response
            ->assertOk()
            ->assertSee('owner-token')
            ->assertDontSee('other-token');
    }

    public function test_active_invite_renders_copyable_link(): void
    {
        $user = User::factory()->create();
        $service = app(RegistrationInviteService::class);
        $invite = $service->createForUser($user);

        $response = $this->actingAs($user)->get(route('settings.registration-invites.index'));

        $response
            ->assertOk()
            ->assertSee('invite=')
            ->assertSee('navigator.clipboard.writeText');

        $this->assertNotNull($service->registrationUrlForInvite($invite));
    }
}
