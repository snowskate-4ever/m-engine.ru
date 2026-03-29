<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('ai_server_model_id')
                ->references('id')
                ->on('ai_server_models')
                ->nullOnDelete();

            $table->foreign('user_ai_connection_id')
                ->references('id')
                ->on('user_ai_connections')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['ai_server_model_id']);
            $table->dropForeign(['user_ai_connection_id']);
        });
    }
};
