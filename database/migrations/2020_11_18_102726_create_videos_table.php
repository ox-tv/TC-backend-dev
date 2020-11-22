<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->string('slug');

            $table->longText('description')->nullable();

            $table->longText('file_path')->nullable();

            $table->longText('youtube_link')->nullable();

            // upload methods: YouTube Import, direct upload
            $table->tinyInteger('upload_method')->default(1);

            // Status: draft, published, archived, suspended
            $table->tinyInteger('status')->default(1);

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

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
        Schema::dropIfExists('videos');
    }
}
