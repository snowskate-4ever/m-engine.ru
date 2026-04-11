<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('platform_fee_rate', 8, 4)->nullable()->after('ogrn');
        });

        Schema::table('shop_orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 15, 2)->default(0)->after('buyer_note');
            $table->decimal('platform_fee_rate', 8, 4)->default(0);
            $table->decimal('platform_fee_amount', 15, 2)->default(0);
            $table->decimal('shop_payout_amount', 15, 2)->default(0);
            $table->string('payment_status', 32)->default('pending');
            $table->string('payment_method', 32)->default('none');
            $table->string('payment_external_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
        });

        $profileTables = [
            'shops',
            'musicians',
            'teachers',
            'peformers',
            'studios',
            'rehearsals',
            'schools',
            'record_labels',
            'producer_centers',
        ];

        foreach ($profileTables as $tbl) {
            if (! Schema::hasTable($tbl)) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $b) use ($tbl) {
                if (! Schema::hasColumn($tbl, 'moderation_hidden_at')) {
                    $b->timestamp('moderation_hidden_at')->nullable();
                }
                if (! Schema::hasColumn($tbl, 'moderation_reason')) {
                    $b->text('moderation_reason')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_amount',
                'platform_fee_rate',
                'platform_fee_amount',
                'shop_payout_amount',
                'payment_status',
                'payment_method',
                'payment_external_reference',
                'paid_at',
            ]);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_rate']);
        });

        $profileTables = [
            'shops',
            'musicians',
            'teachers',
            'peformers',
            'studios',
            'rehearsals',
            'schools',
            'record_labels',
            'producer_centers',
        ];

        foreach ($profileTables as $tbl) {
            if (! Schema::hasTable($tbl) || ! Schema::hasColumn($tbl, 'moderation_hidden_at')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $b) {
                $b->dropColumn(['moderation_hidden_at', 'moderation_reason']);
            });
        }
    }
};
