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
        if (!Schema::hasTable('staff_performance_evaluations')) {
            Schema::create('staff_performance_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('staff_id')->constrained('staffs')->onDelete('cascade');
                $table->unsignedTinyInteger('month');
                $table->unsignedSmallInteger('year');
                $table->json('criteria')->nullable();
                $table->unsignedTinyInteger('positive_points')->default(10);
                $table->unsignedTinyInteger('negative_points')->default(0);
                $table->unsignedTinyInteger('total_points')->default(10);
                $table->decimal('score_percentage', 5, 2)->default(100.00);
                $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'staff_id', 'month', 'year'], 'staff_perf_school_month_year_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_performance_evaluations');
    }
};
