<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonetizationPayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monetization_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('cascade');
            $table->string('wallet_address')->nullable();
            $table->float('amount');
            $table->tinyInteger('status');
            $table->json('parameters');
            $table->json('payment_details');
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
        Schema::dropIfExists('monetization_payouts');
    }
}
