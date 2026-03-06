<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_waves', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_waves', 'target_roles')) {
                $table->json('target_roles')->nullable()->after('label');
            }
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_assignments', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('dispatch_count');
            }

            if (!Schema::hasColumn('survey_assignments', 'invite_status')) {
                $table->string('invite_status')->default('pending')->after('invited_at');
            }

            if (!Schema::hasColumn('survey_assignments', 'invite_error')) {
                $table->text('invite_error')->nullable()->after('invite_status');
            }
        });

        DB::table('survey_waves')
            ->whereNull('target_roles')
            ->update(['target_roles' => json_encode(config('billing.default_wave_roles', [1, 2, 3, 4]))]);
    }

    public function down(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('survey_assignments', 'invite_error')) {
                $table->dropColumn('invite_error');
            }

            if (Schema::hasColumn('survey_assignments', 'invite_status')) {
                $table->dropColumn('invite_status');
            }

            if (Schema::hasColumn('survey_assignments', 'invited_at')) {
                $table->dropColumn('invited_at');
            }
        });

        Schema::table('survey_waves', function (Blueprint $table) {
            if (Schema::hasColumn('survey_waves', 'target_roles')) {
                $table->dropColumn('target_roles');
            }
        });
    }
};
