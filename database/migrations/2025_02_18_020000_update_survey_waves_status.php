<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_waves', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_waves', 'status')) {
                $table->string('status')->default('scheduled')->after('kind');
            }
            if (!Schema::hasColumn('survey_waves', 'cadence')) {
                $table->string('cadence')->default('manual')->after('status');
            }
            if (!Schema::hasColumn('survey_waves', 'last_dispatched_at')) {
                $table->timestamp('last_dispatched_at')->nullable()->after('due_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_waves', function (Blueprint $table) {
            if (Schema::hasColumn('survey_waves', 'last_dispatched_at')) {
                $table->dropColumn('last_dispatched_at');
            }
            if (Schema::hasColumn('survey_waves', 'cadence')) {
                $table->dropColumn('cadence');
            }
            if (Schema::hasColumn('survey_waves', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
