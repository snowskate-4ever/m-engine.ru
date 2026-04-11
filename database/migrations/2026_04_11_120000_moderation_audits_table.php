<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_audits', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor');
            $table->morphs('auditable');
            $table->string('action', 128);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_audits');
    }
};
