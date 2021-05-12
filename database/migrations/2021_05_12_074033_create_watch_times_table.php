<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWatchTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('watch_times', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('video_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('video_id')
                ->references('id')
                ->on('videos');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->unsignedMediumInteger("start_time");
            $table->unsignedMediumInteger("end_time");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('watch_times');
    }
}
