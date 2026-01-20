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
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('is_capital')->default(false)->after('population');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Для SQLite используем более безопасный подход
        if (\DB::getDriverName() === 'sqlite') {
            $this->recreateCitiesTableWithoutIsCapital();
        } else {
            Schema::table('cities', function (Blueprint $table) {
                $table->dropIndex(['is_capital']);
                $table->dropColumn('is_capital');
            });
        }
    }

    private function recreateCitiesTableWithoutIsCapital(): void
    {
        $cities = \DB::table('cities')->get();

        \DB::statement('DROP TABLE IF EXISTS cities');

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable();
            $table->foreignId('country_id');
            $table->string('name');
            $table->string('name_eng')->nullable();
            $table->string('slug');
            $table->string('phone_code')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('code')->nullable();
            $table->string('timezone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('population')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ($cities as $city) {
            \DB::table('cities')->insert([
                'id' => $city->id,
                'region_id' => $city->region_id,
                'country_id' => $city->country_id,
                'name' => $city->name,
                'name_eng' => $city->name_eng,
                'slug' => $city->slug,
                'phone_code' => $city->phone_code,
                'currency_code' => $city->currency_code,
                'currency_symbol' => $city->currency_symbol,
                'code' => $city->code,
                'timezone' => $city->timezone,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'population' => $city->population,
                'is_active' => $city->is_active,
                'sort_order' => $city->sort_order,
                'created_at' => $city->created_at,
                'updated_at' => $city->updated_at,
                'deleted_at' => $city->deleted_at,
            ]);
        }
    }
};
