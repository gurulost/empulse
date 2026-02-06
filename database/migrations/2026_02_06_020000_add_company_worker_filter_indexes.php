<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_worker', function (Blueprint $table) {
            $table->index(['company_id', 'department'], 'cw_company_department_idx');
            $table->index(['company_id', 'supervisor'], 'cw_company_supervisor_idx');
            $table->index(['company_id', 'role'], 'cw_company_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('company_worker', function (Blueprint $table) {
            $table->dropIndex('cw_company_role_idx');
            $table->dropIndex('cw_company_supervisor_idx');
            $table->dropIndex('cw_company_department_idx');
        });
    }
};
