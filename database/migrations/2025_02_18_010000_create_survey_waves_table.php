<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('survey_waves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('survey_id');
            $table->unsignedBigInteger('survey_version_id');
            $table->string('kind')->default('full');
            $table->string('label');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            $table->foreign('survey_version_id')->references('id')->on('survey_versions')->onDelete('cascade');
            $table->index(['company_id', 'kind']);
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_wave_id')->nullable()->after('survey_version_id');
            $table->foreign('survey_wave_id')->references('id')->on('survey_waves')->nullOnDelete();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_wave_id')->nullable()->after('survey_version_id');
            $table->foreign('survey_wave_id')->references('id')->on('survey_waves')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('survey_wave_id');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('survey_wave_id');
        });

        Schema::dropIfExists('survey_waves');
    }
};
