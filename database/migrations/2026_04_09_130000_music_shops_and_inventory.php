<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable()->unique();
            $table->boolean('public_page_enabled')->default(false);
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
            $table->string('legal_entity_type')->nullable();
            $table->string('company_name')->nullable();
            $table->string('inn')->nullable();
            $table->string('ogrn')->nullable();
            $table->timestamps();
        });

        Schema::table('goods', function (Blueprint $table) {
            if (! Schema::hasColumn('goods', 'default_images')) {
                $table->json('default_images')->nullable()->after('description');
            }
        });

        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('good_id')->nullable()->constrained('goods')->nullOnDelete();
            $table->string('code');
            $table->string('condition', 16);
            $table->string('title_override')->nullable();
            $table->text('description_override')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
        });

        Schema::create('shop_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_item_id')->constrained('shop_items')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_item_images');
        Schema::dropIfExists('shop_items');
        Schema::dropIfExists('shops');

        if (Schema::hasTable('goods') && Schema::hasColumn('goods', 'default_images')) {
            Schema::table('goods', function (Blueprint $table) {
                $table->dropColumn('default_images');
            });
        }
    }
};
