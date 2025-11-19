<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_assignments', 'last_dispatched_at')) {
                $table->timestamp('last_dispatched_at')->nullable()->after('last_autosaved_at');
            }

            if (!Schema::hasColumn('survey_assignments', 'dispatch_count')) {
                $table->unsignedInteger('dispatch_count')->default(0)->after('last_dispatched_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('survey_assignments', 'dispatch_count')) {
                $table->dropColumn('dispatch_count');
            }

            if (Schema::hasColumn('survey_assignments', 'last_dispatched_at')) {
                $table->dropColumn('last_dispatched_at');
            }
        });
    }
};
