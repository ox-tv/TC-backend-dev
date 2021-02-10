<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_user', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('channel_id')
                ->references('id')
                ->on('channels');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->primary(['channel_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_user');
    }
}
