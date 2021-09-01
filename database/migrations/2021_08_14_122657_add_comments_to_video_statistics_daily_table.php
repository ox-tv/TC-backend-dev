<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentsToVideoStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_statistics_daily', function (Blueprint $table) {

            $table->after('dislikes_total', function ($table) {
                $table->integer('comments_hero')->default(0);
                $table->integer('comments_non_hero')->default(0);
                $table->integer('comments_total')->default(0);
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
            $table->dropColumn('comments_hero');
            $table->dropColumn('comments_non_hero');
            $table->dropColumn('comments_total');
        });
    }
}
