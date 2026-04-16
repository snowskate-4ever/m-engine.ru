<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->foreignId('verified_booking_id')
                ->nullable()
                ->after('author_user_id')
                ->constrained('bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_booking_id');
        });
    }
};
