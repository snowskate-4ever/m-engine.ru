<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('document_type', 64);
            $table->string('title');
            $table->string('status', 32)->default('draft');
            $table->string('visibility', 32)->default('owner_only');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id'], 'idx_legal_documents_owner');
            $table->index(['status', 'visibility', 'document_type'], 'idx_legal_documents_public_filter');
        });

        Schema::create('legal_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_id')->constrained('legal_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('payload_json')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['legal_document_id', 'version']);
            $table->index(['effective_from', 'effective_to'], 'idx_legal_document_versions_effective');
        });

        // Adding FK after table creation to avoid circular dependency issues
        Schema::table('legal_documents', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'legal_documents_current_version_fk')
                ->references('id')
                ->on('legal_document_versions')
                ->nullOnDelete();
        });

        Schema::create('legal_document_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_id')->constrained('legal_documents')->cascadeOnDelete();
            $table->string('action', 32);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');

            $table->index(['legal_document_id', 'performed_at'], 'idx_legal_document_audits_doc_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_audits');
        Schema::table('legal_documents', function (Blueprint $table): void {
            $table->dropForeign('legal_documents_current_version_fk');
        });
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
    }
};
