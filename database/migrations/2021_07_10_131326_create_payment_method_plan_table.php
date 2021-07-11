<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentMethodPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_method_plan', function (Blueprint $table) {
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

            $table->primary(['plan_id', 'payment_method_id', 'currency']);
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
