<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_workspace_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('advisor_company_grant_id')->nullable()
                ->constrained('advisor_company_grants')->restrictOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            $table->enum('visibility', ['customer_shared', 'workfit_internal']);
            $table->text('body');
            $table->timestamp('created_at');

            $table->index(
                ['company_id', 'visibility', 'created_at'],
                'advisor_notes_visibility_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_workspace_notes');
    }
};
