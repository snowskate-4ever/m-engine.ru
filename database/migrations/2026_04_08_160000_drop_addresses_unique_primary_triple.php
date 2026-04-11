<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Уникальность на (addressable_id, addressable_type, is_primary) запрещает несколько адресов с is_primary=false;
     * логика «один основной» уже в модели Address (boot). Индекс удаляем.
     */
    public function up(): void
    {
        if (! Schema::hasTable('addresses')) {
            return;
        }

        if (Schema::hasIndex('addresses', 'unique_primary_address')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->dropUnique('unique_primary_address');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('addresses')) {
            return;
        }

        Schema::table('addresses', function (Blueprint $table) {
            $table->unique(['addressable_id', 'addressable_type', 'is_primary'], 'unique_primary_address');
        });
    }
};
