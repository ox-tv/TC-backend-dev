<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id()->startingValue(10000020);

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');

            $table->string('name');
            $table->tinyInteger('status');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_slots', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ad_campaign_id');
            $table->foreign('ad_campaign_id')->references('id')->on('ad_campaigns');

            $table->date('date');
            $table->string('tier');
            $table->decimal("price")->nullable();
            $table->unsignedSmallInteger("quantity")->default(0);
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
        Schema::dropIfExists('ad_campaign_slots');
        Schema::dropIfExists('ad_campaigns');
    }
}
