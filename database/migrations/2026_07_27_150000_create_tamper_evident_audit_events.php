<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('stream_key');
            $table->unsignedBigInteger('sequence');
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->string('event_hash', 64)->unique();
            $table->timestamp('occurred_at');

            $table->unique(['stream_key', 'sequence']);
            $table->index(['company_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
