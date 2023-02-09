<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_campaigns', function (Blueprint $table) {
            $table->id()->startingValue(10020);
            $table->string('name');
            $table->text('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('exchange_name')->nullable();
            $table->string('exchange_main_url')->nullable();
            $table->string('exchange_referral_url');
            $table->string('thumbnail')->nullable();
            $table->unsignedTinyInteger('status');
            $table->timestamps();
        });

        Schema::create('campaign_crypto_currency', function (Blueprint $table) {
            $table->unsignedBigInteger('crypto_currency_id');
            $table->unsignedBigInteger('crypto_campaign_id');

            $table->foreign('crypto_currency_id')
                ->references('id')
                ->on('crypto_currencies');

            $table->foreign('crypto_campaign_id')
                ->references('id')
                ->on('crypto_campaigns');

            $table->primary(['crypto_currency_id', 'crypto_campaign_id'], 'campaign_crypto_currency_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_campaigns');
    }
}
