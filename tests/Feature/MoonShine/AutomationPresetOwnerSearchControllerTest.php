<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use Tests\TestCase;

class AutomationPresetOwnerSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search_owners(): void
    {
        $this->get(route('moonshine.async.automation-preset-owner'))
            ->assertRedirect();
    }

    public function test_moonshine_user_receives_json_options_for_users(): void
    {
        $admin = MoonshineUser::factory()->create();
        $target = User::factory()->create([
            'name' => 'Unique Async Owner',
            'email' => 'unique-async-owner@example.com',
        ]);

        $response = $this->actingAs($admin, 'moonshine')
            ->get(route('moonshine.async.automation-preset-owner', [
                'query' => 'Unique Async',
                'owner_type' => User::class,
            ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $values = array_column($data, 'value');
        $this->assertContains((string) $target->id, $values);
    }
}
