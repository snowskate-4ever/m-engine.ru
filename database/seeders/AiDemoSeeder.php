<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use App\Models\AiServerModel;
use Illuminate\Database\Seeder;

/**
 * Inserts a demo OpenAI-compatible provider + model for local AI chats.
 * API key: set AI_OPENAI_SERVER_API_KEY or configure ai_providers.config in DB / Moonshine.
 */
class AiDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $provider = AiProvider::query()->updateOrCreate(
            [
                'driver' => 'openai',
                'name' => 'OpenAI (local demo)',
            ],
            [
                'config' => null,
                'scope' => 'server',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        AiServerModel::query()->updateOrCreate(
            [
                'ai_provider_id' => $provider->id,
                'vendor_model_id' => 'gpt-4o-mini',
            ],
            [
                'display_name' => 'GPT-4o mini (demo)',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}
