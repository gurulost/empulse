<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_versions', function (Blueprint $table) {
            $table->string('publication_status', 24)->default('draft')->index();
            $table->text('change_summary')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
        });

        DB::table('survey_versions')
            ->where('is_active', true)
            ->update([
                'publication_status' => 'published',
                'change_summary' => DB::raw("COALESCE(source_note, 'Legacy active instrument migrated into the governed publication workflow.')"),
                'reviewed_by' => DB::raw('published_by'),
                'reviewed_at' => DB::raw('published_at'),
                'approved_by' => DB::raw('published_by'),
                'approved_at' => DB::raw('published_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('survey_versions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'publication_status',
                'change_summary',
                'reviewed_at',
                'approved_at',
            ]);
        });
    }
};
