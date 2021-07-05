<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCryptoCurrencyUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_currency_user', function (Blueprint $table) {
            $table->unsignedBigInteger('crypto_currency_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('crypto_currency_id')
                ->references('id')
                ->on('crypto_currencies');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->primary(['crypto_currency_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_currency_user');
    }
}
