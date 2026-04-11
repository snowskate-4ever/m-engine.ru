<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\RegistrationInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response
            ->assertStatus(200)
            ->assertSee(__('ui.auth.register.invite_required'))
            ->assertDontSee('data-test="register-user-button"', false);
    }

    public function test_new_users_can_register_with_active_invite(): void
    {
        $inviteService = app(RegistrationInviteService::class);
        $invite = $inviteService->createForUser(\App\Models\User::factory()->create());
        $inviteUrl = $inviteService->registrationUrlForInvite($invite);
        parse_str((string) parse_url((string) $inviteUrl, PHP_URL_QUERY), $query);

        $response = $this->post(route('register.store'), [
            'invite' => $query['invite'] ?? '',
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_cannot_register_without_valid_invite(): void
    {
        $response = $this->post(route('register.store'), [
            'invite' => 'invalid-token',
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('invite');
        $this->assertGuest();
    }

    public function test_invite_token_is_one_time_only(): void
    {
        $inviteService = app(RegistrationInviteService::class);
        $invite = $inviteService->createForUser(\App\Models\User::factory()->create());
        $inviteUrl = $inviteService->registrationUrlForInvite($invite);
        parse_str((string) parse_url((string) $inviteUrl, PHP_URL_QUERY), $query);
        $token = $query['invite'] ?? '';

        $firstResponse = $this->post(route('register.store'), [
            'invite' => $token,
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $firstResponse->assertSessionHasNoErrors();
        auth()->logout();

        $secondResponse = $this->post(route('register.store'), [
            'invite' => $token,
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $secondResponse->assertSessionHasErrors('invite');
    }
}
