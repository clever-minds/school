<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'announcements',
            'exams',
            'expense_categories',
            'expenses',
            'fees_types',
            'galleries',
            'holidays',
            'lesson_topics',
            'lessons',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'description')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->longText('description')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
