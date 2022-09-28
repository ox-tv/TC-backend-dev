<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSomeSocialToChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('telegram')->after('website')->nullable();
            $table->string('reddit')->after('website')->nullable();
            $table->string('linkedin')->after('website')->nullable();
            $table->string('tiktok')->after('website')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('telegram');
            $table->dropColumn('reddit');
            $table->dropColumn('linkedin');
            $table->dropColumn('tiktok');
        });
    }
}
