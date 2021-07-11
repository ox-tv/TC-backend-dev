<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricing', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('payment_method_id');

            $table->foreign('plan_id')
                ->references('id')
                ->on('plans');

            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods');

            $table->string("currency");
            $table->decimal("amount", 16,8);
            $table->string("external_id");

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
        Schema::dropIfExists('payment_method_plan');
    }
}
