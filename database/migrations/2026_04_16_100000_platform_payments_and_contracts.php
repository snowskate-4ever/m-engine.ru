<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payer_user_id')->constrained('users')->cascadeOnDelete();
            $table->nullableMorphs('payable');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 8)->default('RUB');
            $table->string('status', 32)->default('pending')->index();
            $table->boolean('use_escrow')->default(false);
            $table->unsignedInteger('platform_fee_bps')->default(0);
            $table->unsignedBigInteger('platform_fee_minor')->default(0);
            $table->string('driver', 32)->default('stub');
            $table->string('external_id')->nullable()->index();
            $table->json('driver_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_payment_id')->constrained('platform_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('reason')->nullable();
            $table->string('policy_label')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_payout_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_template_id')->constrained('contract_templates')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->longText('body_template');
            $table->json('variables_schema')->nullable();
            $table->timestamps();

            $table->unique(['contract_template_id', 'version']);
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_template_version_id')->constrained('contract_template_versions')->cascadeOnDelete();
            $table->nullableMorphs('party_a');
            $table->nullableMorphs('party_b');
            $table->longText('rendered_body');
            $table->json('filled_variables')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestampTz('party_a_accepted_at')->nullable();
            $table->timestampTz('party_b_accepted_at')->nullable();
            $table->foreignId('party_a_accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('party_b_accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contract_acceptance_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('side', 8);
            $table->string('action', 32);
            $table->json('payload')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_acceptance_audits');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_template_versions');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('platform_payout_batches');
        Schema::dropIfExists('platform_payment_refunds');
        Schema::dropIfExists('platform_payments');
    }
};
