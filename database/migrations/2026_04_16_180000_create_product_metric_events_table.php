<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_metric_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_name', 120);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 40)->default('web');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['event_name', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['channel', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_metric_events');
    }
};
