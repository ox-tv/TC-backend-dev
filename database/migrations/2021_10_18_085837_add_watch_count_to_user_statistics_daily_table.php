<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWatchCountToUserStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_statistics_daily', function (Blueprint $table) {
            $table->integer('video_watch_count_as_hero')->default(0);
            $table->integer('video_watch_count_as_non_hero')->default(0);
            $table->integer('video_watch_count_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_statistics_daily', function (Blueprint $table) {
            $table->dropColumn('video_watch_count_as_hero');
            $table->dropColumn('video_watch_count_as_non_hero');
            $table->dropColumn('video_watch_count_total');
        });
    }
}
