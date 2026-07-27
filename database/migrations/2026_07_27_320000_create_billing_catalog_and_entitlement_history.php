<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_catalog_versions', function (Blueprint $table) {
            $table->id();
            $table->char('definition_hash', 64)->unique();
            $table->json('definition');
            $table->string('status')->default('published');
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_at');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('organization_entitlements', function (Blueprint $table) {
            $table->foreignId('billing_catalog_version_id')->nullable()
                ->constrained('billing_catalog_versions')->restrictOnDelete();
        });

        Schema::create('organization_entitlement_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->unsignedBigInteger('version');
            $table->foreignId('billing_catalog_version_id')
                ->constrained('billing_catalog_versions')->restrictOnDelete();
            $table->string('plan_key');
            $table->string('status');
            $table->string('source');
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('recorded_at');

            $table->unique(['company_id', 'version']);
            $table->index(['company_id', 'plan_key', 'recorded_at'], 'org_entitlement_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_entitlement_versions');
        Schema::table('organization_entitlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_catalog_version_id');
        });
        Schema::dropIfExists('billing_catalog_versions');
    }
};
