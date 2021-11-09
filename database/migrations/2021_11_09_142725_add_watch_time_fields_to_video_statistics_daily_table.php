<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWatchTimeFieldsToVideoStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_statistics_daily', function (Blueprint $table) {
            $table->after('comments_hero', function ($table) {
                $table->unsignedInteger('watch_time_hero')->default(0);
                $table->unsignedInteger('watch_time_non_hero')->default(0);
                $table->unsignedInteger('watch_time_total')->default(0);
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('video_statistics_daily', function (Blueprint $table) {
            $table->dropColumn('watch_time_hero');
            $table->dropColumn('watch_time_non_hero');
            $table->dropColumn('watch_time_total');
        });
    }
}
