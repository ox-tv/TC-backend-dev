<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricingUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricing_user', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('pricing_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('pricing_id')
                ->references('id')
                ->on('pricing');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->json('metadata')->nullable();
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
        Schema::dropIfExists('pricing_user');
    }
}
