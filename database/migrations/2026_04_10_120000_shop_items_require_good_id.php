<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shop_items')->whereNull('good_id')->delete();

        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropForeign(['good_id']);
        });

        Schema::table('shop_items', function (Blueprint $table) {
            $table->foreignId('good_id')->nullable(false)->change();
            $table->foreign('good_id')->references('id')->on('goods')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropForeign(['good_id']);
        });

        Schema::table('shop_items', function (Blueprint $table) {
            $table->foreignId('good_id')->nullable()->change();
            $table->foreign('good_id')->references('id')->on('goods')->nullOnDelete();
        });
    }
};
