<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ApiFeaturesTest extends TestCase
{
    public function test_features_returns_ai_enabled_true(): void
    {
        config(['ai.enabled' => true]);

        $this->getJson('/api/features')
            ->assertOk()
            ->assertJsonPath('data.ai_enabled', true);
    }

    public function test_features_returns_ai_enabled_false(): void
    {
        config(['ai.enabled' => false]);

        $this->getJson('/api/features')
            ->assertOk()
            ->assertJsonPath('data.ai_enabled', false);
    }

    public function test_features_does_not_require_authentication(): void
    {
        config(['ai.enabled' => true]);

        $this->getJson('/api/features')->assertOk();
    }
}
