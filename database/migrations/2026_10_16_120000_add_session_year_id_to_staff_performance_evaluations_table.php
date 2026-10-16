<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('staff_performance_evaluations')) {
            Schema::table('staff_performance_evaluations', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_performance_evaluations', 'session_year_id')) {
                    $table->foreignId('session_year_id')->nullable()->after('staff_id')->constrained('session_years')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('staff_performance_evaluations')) {
            Schema::table('staff_performance_evaluations', function (Blueprint $table) {
                if (Schema::hasColumn('staff_performance_evaluations', 'session_year_id')) {
                    $table->dropForeign(['session_year_id']);
                    $table->dropColumn('session_year_id');
                }
            });
        }
    }
};
