<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->string('wave_label')->nullable()->after('survey_version_id');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('wave_label')->nullable()->after('survey_version_id');
        });
    }

    public function down()
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropColumn('wave_label');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn('wave_label');
        });
    }
};
