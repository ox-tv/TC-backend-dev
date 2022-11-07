<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCryptoCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            $table->dropUnique(['coinmarketcap_id']);
            $table->dropColumn("coinmarketcap_id");
            $table->unique('slug');
            $table->unsignedInteger('order')->default(1000000)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            $table->unsignedBigInteger('coinmarketcap_id');
            $table->unsignedInteger('order')->default(100000)->change();
            $table->dropUnique(['slug']);
            $table->unique('coinmarketcap_id');
        });
    }
}
