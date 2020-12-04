<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->longText('description')->nullable();

            $table->string('slug')->unique()->nullable();
            $table->string('url_hash')->unique()->nullable();

            $table->longText('cover')->nullable();
            $table->longText('image')->nullable();

            $table->unsignedBigInteger('intro_video_id')->nullable();
            $table->foreign('intro_video_id')
                ->references('id')
                ->on('videos');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->tinyInteger('status')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channels');
    }
}
