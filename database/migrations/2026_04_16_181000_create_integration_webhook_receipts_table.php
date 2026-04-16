<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_webhook_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 120)->unique();
            $table->string('event_name', 120);
            $table->string('signature', 255)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_webhook_receipts');
    }
};
