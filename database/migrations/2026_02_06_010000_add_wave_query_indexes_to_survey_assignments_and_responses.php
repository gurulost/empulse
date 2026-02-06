<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->index('survey_wave_id', 'sa_wave_idx');
            $table->index('survey_version_id', 'sa_version_idx');
            $table->index(['survey_wave_id', 'status'], 'sa_wave_status_idx');
            $table->index(['survey_wave_id', 'last_dispatched_at'], 'sa_wave_dispatch_idx');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->index('assignment_id', 'sr_assignment_idx');
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropIndex('sr_assignment_idx');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropIndex('sa_wave_dispatch_idx');
            $table->dropIndex('sa_wave_status_idx');
            $table->dropIndex('sa_version_idx');
            $table->dropIndex('sa_wave_idx');
        });
    }
};
