<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_external_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('external_id', 100);
            $table->string('external_id_normalized', 100);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'external_id_normalized'], 'roster_external_company_id_unique');
            $table->unique(['company_id', 'user_id'], 'roster_external_company_user_unique');
        });

        Schema::create('roster_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('source_sha256', 64);
            $table->longText('source_csv')->nullable();
            $table->string('status')->default('parsing');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('create_count')->default(0);
            $table->unsignedInteger('update_count')->default(0);
            $table->unsignedInteger('reactivate_count')->default(0);
            $table->unsignedInteger('deactivate_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->string('confirmation_token_hash', 64)->nullable();
            $table->timestamp('confirmation_expires_at')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('rows_purged_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_summary')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'source_sha256'], 'roster_import_company_source_unique');
            $table->index(['company_id', 'status', 'created_at'], 'roster_import_company_status_idx');
        });

        Schema::create('roster_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_import_id')->constrained('roster_imports')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('external_id', 100)->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('role')->nullable();
            $table->string('department')->nullable();
            $table->string('supervisor_external_id', 100)->nullable();
            $table->string('desired_status')->nullable();
            $table->string('action')->default('invalid');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_fingerprint', 64)->nullable();
            $table->json('changes')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->unique(['roster_import_id', 'row_number'], 'roster_import_row_number_unique');
            $table->index(['roster_import_id', 'external_id'], 'roster_import_external_idx');
            $table->index(['roster_import_id', 'email'], 'roster_import_email_idx');
            $table->index(['roster_import_id', 'action'], 'roster_import_action_idx');
        });

        Schema::table('account_invitations', function (Blueprint $table) {
            $table->longText('delivery_token')->nullable();
            $table->uuid('delivery_idempotency_key')->nullable()->unique();
            $table->string('delivery_status')->default('pending');
            $table->unsignedSmallInteger('delivery_attempts')->default(0);
            $table->timestamp('delivery_last_attempt_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('delivery_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('account_invitations', function (Blueprint $table) {
            $table->dropUnique(['delivery_idempotency_key']);
            $table->dropColumn([
                'delivery_token',
                'delivery_idempotency_key',
                'delivery_status',
                'delivery_attempts',
                'delivery_last_attempt_at',
                'provider_message_id',
                'delivery_error',
                'delivered_at',
            ]);
        });

        Schema::dropIfExists('roster_import_rows');
        Schema::dropIfExists('roster_imports');
        Schema::dropIfExists('roster_external_identities');
    }
};
