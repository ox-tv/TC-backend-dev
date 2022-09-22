<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->unsignedTinyInteger('status');
            $table->string('proof_code');

            $table->string('first_name');
            $table->string('last_name');
            $table->text('street_address');
            $table->text('street_number');
            $table->string('postal_code');
            $table->string('city');
            $table->string('country');
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('eth_address');

            $table->timestamps();
            $table->timestamp('last_status_at');
            $table->timestamp('code_sent_at')->nullable();

            $table->boolean('is_archive');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_details');
    }
}
