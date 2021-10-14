<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_statistics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->integer('video_views_count_as_hero')->default(0);
            $table->integer('video_views_count_as_non_hero')->default(0);
            $table->integer('video_views_count_total')->default(0);

            $table->integer('video_likes_count_as_hero')->default(0);
            $table->integer('video_likes_count_as_non_hero')->default(0);
            $table->integer('video_likes_count_total')->default(0);

            $table->integer('comment_likes_count_as_hero')->default(0); // user did
            $table->integer('comment_likes_count_as_non_hero')->default(0);
            $table->integer('comment_likes_count_total')->default(0);

            $table->integer('comment_liked_count_as_hero')->default(0); // others did on user comments :)
            $table->integer('comment_liked_count_as_non_hero')->default(0);
            $table->integer('comment_liked_count_total')->default(0);

            $table->integer('referral_count_as_hero')->default(0);
            $table->integer('referral_count_as_non_hero')->default(0);
            $table->integer('referral_count_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_statistics_daily');
    }
}
