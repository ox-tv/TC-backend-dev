<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWatchHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('watch_hours', function (Blueprint $table) {
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

            $table->primary(['message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('watch_hours');
    }
}
