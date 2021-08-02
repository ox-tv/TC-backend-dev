<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnsDataTypeInVideoStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_statistics_daily', function (Blueprint $table) {
            $table->integer('likes_hero')->change();
            $table->integer('likes_non_hero')->change();
            $table->integer('likes_total')->change();

            $table->integer('dislikes_hero')->change();
            $table->integer('dislikes_non_hero')->change();
            $table->integer('dislikes_total')->change();
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
            $table->unsignedInteger('likes_hero')->change();
            $table->unsignedInteger('likes_non_hero')->change();
            $table->unsignedInteger('likes_total')->change();

            $table->unsignedInteger('dislikes_hero')->change();
            $table->unsignedInteger('dislikes_non_hero')->change();
            $table->unsignedInteger('dislikes_total')->change();
        });
    }
}
