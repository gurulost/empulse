<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_company_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('advisor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('active');
            $table->text('purpose');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'advisor_user_id']);
            $table->index(['advisor_user_id', 'status', 'valid_from', 'valid_until'], 'advisor_active_grants_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_company_grants');
    }
};
