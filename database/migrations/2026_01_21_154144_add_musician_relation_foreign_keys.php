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
        if (Schema::hasTable('musician_genre')) {
            Schema::table('musician_genre', function (Blueprint $table) {
                $table->foreign('musician_id')->references('id')->on('musicians')->onDelete('cascade');
                $table->foreign('genre_id')->references('id')->on('genres')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('musician_instrument')) {
            Schema::table('musician_instrument', function (Blueprint $table) {
                $table->foreign('musician_id')->references('id')->on('musicians')->onDelete('cascade');
                $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('musician_genre')) {
            Schema::table('musician_genre', function (Blueprint $table) {
                try {
                    $table->dropForeign(['musician_id']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropForeign(['genre_id']);
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('musician_instrument')) {
            Schema::table('musician_instrument', function (Blueprint $table) {
                try {
                    $table->dropForeign(['musician_id']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropForeign(['instrument_id']);
                } catch (\Throwable $e) {
                }
            });
        }
    }
};
