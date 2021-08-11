<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_statistics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');

            $table->unsignedBigInteger('channel_id');
            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('cascade');

            $table->integer('subscribers_hero')->default(0);
            $table->integer('subscribers_non_hero')->default(0);
            $table->integer('subscribers_total')->default(0);

            $table->integer('unsubscribers_hero')->default(0);
            $table->integer('unsubscribers_non_hero')->default(0);
            $table->integer('unsubscribers_total')->default(0);

            $table->integer('upload_videos_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_statistics_daily');
    }
}
