<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_labels', function (Blueprint $table) {
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

        Schema::create('producer_centers', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_centers');
        Schema::dropIfExists('record_labels');
    }
};
