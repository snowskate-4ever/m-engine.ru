<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_item_id')->constrained('shop_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['user_id', 'shop_item_id']);
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32);
            $table->text('buyer_note')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->foreignId('shop_item_id')->constrained('shop_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->string('title_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_cart_items');
    }
};
