<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('type', 50);
            $table->jsonb('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('webhook_url', 500)->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_channels');
    }
};
