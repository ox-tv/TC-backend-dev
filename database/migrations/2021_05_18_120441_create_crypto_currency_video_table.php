<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoCurrencyVideoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_currency_video', function (Blueprint $table) {
            $table->unsignedBigInteger('crypto_currency_id');
            $table->unsignedBigInteger('video_id');

            $table->foreign('crypto_currency_id')
                ->references('id')
                ->on('crypto_currencies');

            $table->foreign('video_id')
                ->references('id')
                ->on('videos');

            $table->primary(['crypto_currency_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_currency_video');
    }
}
