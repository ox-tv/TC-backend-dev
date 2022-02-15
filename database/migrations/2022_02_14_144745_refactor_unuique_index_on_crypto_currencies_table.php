<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorUnuiqueIndexOnCryptoCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique('coinmarketcap_id');
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
            $table->dropUnique(['coinmarketcap_id']);
            $table->unique('slug');
        });
    }
}
