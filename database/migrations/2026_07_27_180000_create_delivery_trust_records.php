<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('email');
            $table->string('status')->default('deliverable');
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('suppressed_at')->nullable();
            $table->string('suppression_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'email']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('email_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_contact_id')->constrained('delivery_contacts')->restrictOnDelete();
            $table->foreignId('survey_assignment_id')->nullable()->constrained('survey_assignments')->restrictOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('message_kind');
            $table->string('status');
            $table->string('provider')->default('brevo');
            $table->string('provider_message_id')->nullable()->index();
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['survey_assignment_id', 'message_kind', 'status'], 'delivery_assignment_funnel_idx');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamp('last_reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropColumn(['reminder_count', 'last_reminded_at']);
        });
        Schema::dropIfExists('email_delivery_events');
        Schema::dropIfExists('delivery_contacts');
    }
};
