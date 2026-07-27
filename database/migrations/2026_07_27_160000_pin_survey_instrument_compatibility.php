<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('instrument_id')->nullable()->index();
        });

        Schema::table('survey_versions', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['instrument_id', 'version'], 'survey_instrument_version_unique');
        });
    }

    public function down(): void
    {
        Schema::table('survey_versions', function (Blueprint $table) {
            $table->dropUnique('survey_instrument_version_unique');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['content_hash', 'published_at']);
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('instrument_id');
        });
    }
};
