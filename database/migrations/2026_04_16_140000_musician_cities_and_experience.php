<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('musicians') && ! Schema::hasColumn('musicians', 'years_of_experience')) {
            Schema::table('musicians', function (Blueprint $table) {
                $table->unsignedSmallInteger('years_of_experience')->nullable()->after('bio');
            });
        }

        if (! Schema::hasTable('musician_city')) {
            Schema::create('musician_city', function (Blueprint $table) {
                $table->id();
                $table->foreignId('musician_id')->constrained('musicians')->cascadeOnDelete();
                $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['musician_id', 'city_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('musician_city');

        if (Schema::hasTable('musicians') && Schema::hasColumn('musicians', 'years_of_experience')) {
            Schema::table('musicians', function (Blueprint $table) {
                $table->dropColumn('years_of_experience');
            });
        }
    }
};
