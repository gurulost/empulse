<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('role');
            $table->timestamp('left_at')->nullable()->after('status');
            $table->index(['company_id', 'status']);
        });

        Schema::table('company_worker', function (Blueprint $table) {
            $table->string('status')->default('active')->after('role');
            $table->timestamp('left_at')->nullable()->after('status');
            $table->index(['company_id', 'status']);
        });

        Schema::create('account_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 125);
            $table->unsignedTinyInteger('role');
            $table->string('token_hash', 64)->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_invitations');

        Schema::table('company_worker', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropColumn(['status', 'left_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropColumn(['status', 'left_at']);
        });
    }
};
