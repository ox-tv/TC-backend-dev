<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_statistics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');

            $table->unsignedBigInteger('video_id');
            $table->foreign('video_id')
                ->references('id')
                ->on('videos')
                ->onDelete('cascade');

            $table->unsignedBigInteger('channel_id');
            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('cascade');

            $table->unsignedInteger('views_hero')->default(0);
            $table->unsignedInteger('views_non_hero')->default(0);
            $table->unsignedInteger('views_total')->default(0);

            $table->unsignedInteger('likes_hero')->default(0);
            $table->unsignedInteger('likes_non_hero')->default(0);
            $table->unsignedInteger('likes_total')->default(0);

            $table->unsignedInteger('dislikes_hero')->default(0);
            $table->unsignedInteger('dislikes_non_hero')->default(0);
            $table->unsignedInteger('dislikes_total')->default(0);

            $table->decimal('points',16,8)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('video_statistics_daily');
    }
}
