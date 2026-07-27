<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->unique();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });

        Schema::create('organization_billing_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role')->default('admin');
            $table->string('status')->default('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        Schema::create('organization_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->restrictOnDelete();
            $table->string('plan_key')->default('none');
            $table->string('status')->default('none');
            $table->string('source')->default('none');
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_price_id')->nullable();
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('last_stripe_event_at')->nullable();
            $table->timestamp('last_reconciled_at')->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('billing_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->string('event_type');
            $table->string('payload_hash', 64);
            $table->timestamp('stripe_created_at')->nullable();
            $table->string('status')->default('processing');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('metric');
            $table->decimal('quantity', 14, 4);
            $table->string('unit');
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'metric', 'occurred_at'], 'org_usage_metric_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_usage_events');
        Schema::dropIfExists('billing_webhook_events');
        Schema::dropIfExists('organization_entitlements');
        Schema::dropIfExists('organization_billing_admins');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at']);
        });
    }
};
