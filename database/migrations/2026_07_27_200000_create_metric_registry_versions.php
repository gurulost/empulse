<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_registry_versions', function (Blueprint $table) {
            $table->id();
            $table->string('registry_key', 100);
            $table->string('version', 64);
            $table->char('definition_hash', 64)->unique();
            $table->json('definition');
            $table->enum('status', ['draft', 'published', 'retired'])->default('published');
            $table->timestamp('published_at');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['registry_key', 'version']);
        });

        Schema::table('survey_wave_cycles', function (Blueprint $table) {
            $table->foreignId('metric_registry_version_id')
                ->nullable()
                ->constrained('metric_registry_versions')
                ->restrictOnDelete();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('metric_registry_version_id')
                ->nullable()
                ->constrained('metric_registry_versions')
                ->restrictOnDelete();
            $table->char('metric_definition_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metric_registry_version_id');
            $table->dropColumn('metric_definition_hash');
        });
        Schema::table('survey_wave_cycles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metric_registry_version_id');
        });
        Schema::dropIfExists('metric_registry_versions');
    }
};
