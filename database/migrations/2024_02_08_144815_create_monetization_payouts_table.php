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
            $table->unsignedBigInteger('monetization_id');
            $table->foreign('monetization_id')
                ->references('id')
                ->on('monetization')
                ->onDelete('cascade');
            $table->unsignedBigInteger('channel_id');
            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('cascade');
            $table->tinyInteger('status');
            $table->string('wallet_address')->nullable();
            $table->float('amount')->nullable();
            $table->json('metrics')->nullable();
            $table->json('payment_details')->nullable();
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
