<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ai_subscriptions', function (Blueprint $table) {
            $table->unique(['payment_provider', 'external_payment_ref'], 'user_ai_subs_provider_ext_ref_unique');
        });

        Schema::table('ai_request_logs', function (Blueprint $table) {
            $table->longText('prompt_full')->nullable()->after('response_excerpt');
            $table->longText('response_full')->nullable()->after('prompt_full');
        });
    }

    public function down(): void
    {
        Schema::table('ai_request_logs', function (Blueprint $table) {
            $table->dropColumn(['prompt_full', 'response_full']);
        });

        Schema::table('user_ai_subscriptions', function (Blueprint $table) {
            $table->dropUnique('user_ai_subs_provider_ext_ref_unique');
        });
    }
};
