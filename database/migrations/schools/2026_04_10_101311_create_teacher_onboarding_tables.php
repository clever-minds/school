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
        Schema::create('teacher_onboarding_jds', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->bigInteger('school_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teacher_onboarding_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->string('answer'); // 'a', 'b', 'c', or 'd'
            $table->bigInteger('school_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teacher_onboarding_results', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->integer('score');
            $table->integer('total_questions');
            $table->boolean('status')->default(0); // 0: Fail/Ongoing, 1: Pass
            $table->bigInteger('school_id')->unsigned();
            $table->timestamps();
        });

        if (!Schema::hasColumn('staff', 'onboarding_completed')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->boolean('onboarding_completed')->default(0)->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_onboarding_jds');
        Schema::dropIfExists('teacher_onboarding_questions');
        Schema::dropIfExists('teacher_onboarding_results');
        
        if (Schema::hasColumn('staff', 'onboarding_completed')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('onboarding_completed');
            });
        }
    }
};
