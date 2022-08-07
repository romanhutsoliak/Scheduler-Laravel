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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('periodType')->nullable()->default(null);
            $table->string('periodTypeTime', 10)->nullable()->default(null);
            $table->text('periodTypeWeekDays')->nullable()->default(null);
            $table->text('periodTypeMonthDays')->nullable()->default(null);
            $table->text('periodTypeMonths')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function ($table) {
            $table->dropColumn(['periodType', 'periodTypeTime', 'periodTypeWeekDays', 'periodTypeMonthDays', 'periodTypeMonths']);
        });
    }
};
