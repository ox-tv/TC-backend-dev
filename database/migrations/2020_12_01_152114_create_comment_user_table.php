<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comment_user', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');

            $table->tinyInteger('relation');

            $table->foreign('comment_id')
                ->references('id')
                ->on('comments');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->primary(['comment_id', 'user_id', 'relation']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comment_user');
    }
}
