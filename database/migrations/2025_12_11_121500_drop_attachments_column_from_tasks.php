<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tasks', 'attachments')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('attachments');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tasks', 'attachments')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->json('attachments')->nullable();
            });
        }
    }
};





