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
        if (!Schema::hasTable('teacher_documents')) {
            Schema::create('teacher_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->string('type'); // id_proof, address_proof, marksheet, degree, experience_letter
                $table->string('file_url');
                $table->tinyInteger('status')->default(0)->comment('0: Pending, 1: Approved, 2: Rejected');
                $table->text('rejection_reason')->nullable();
                $table->foreignId('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('staffs', 'kyc_completed')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->boolean('kyc_completed')->default(0)->after('onboarding_completed');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_documents');
        if (Schema::hasColumn('staffs', 'kyc_completed')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->dropColumn('kyc_completed');
            });
        }
    }
};
