<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_profile_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reportable');
            $table->string('reason', 2000);
            $table->string('status', 32)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
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
                if (! Schema::hasColumn($tbl, 'moderation_review_requested_at')) {
                    $b->timestamp('moderation_review_requested_at')->nullable()->after('moderation_reason');
                }
            });
        }

        if (Schema::hasTable('shop_orders')) {
            Schema::table('shop_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_orders', 'delivery_mode')) {
                    $table->string('delivery_mode', 32)->default('pickup')->after('buyer_note');
                }
                if (! Schema::hasColumn('shop_orders', 'shipping_address_id')) {
                    $table->foreignId('shipping_address_id')->nullable()->after('delivery_mode')->constrained('addresses')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shop_orders')) {
            Schema::table('shop_orders', function (Blueprint $table) {
                if (Schema::hasColumn('shop_orders', 'shipping_address_id')) {
                    $table->dropConstrainedForeignId('shipping_address_id');
                }
                if (Schema::hasColumn('shop_orders', 'delivery_mode')) {
                    $table->dropColumn('delivery_mode');
                }
            });
        }

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
            if (! Schema::hasTable($tbl) || ! Schema::hasColumn($tbl, 'moderation_review_requested_at')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $b) {
                $b->dropColumn('moderation_review_requested_at');
            });
        }

        Schema::dropIfExists('public_profile_reports');
    }
};
