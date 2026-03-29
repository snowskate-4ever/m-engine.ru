<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_subscription_tiers', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('price_monthly_rub');
            $table->decimal('discount_amount_fixed', 12, 2)->nullable()->after('discount_percent');
            $table->timestampTz('discount_valid_until')->nullable()->after('discount_amount_fixed');
        });
    }

    public function down(): void
    {
        Schema::table('ai_subscription_tiers', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_amount_fixed', 'discount_valid_until']);
        });
    }
};
