<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_webhook_receipts', function (Blueprint $table): void {
            $table->string('status', 32)->default('completed')->after('event_name');
            $table->text('error_message')->nullable()->after('status');
            $table->string('client_ip', 45)->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('integration_webhook_receipts', function (Blueprint $table): void {
            $table->dropColumn(['status', 'error_message', 'client_ip']);
        });
    }
};
