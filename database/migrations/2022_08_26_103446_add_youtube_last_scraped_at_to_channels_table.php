<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYoutubeLastScrapedAtToChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->timestamp('youtube_last_scraped_at')->after('youtube_channel_id')->nullable();
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
            $table->dropColumn('youtube_last_scraped_at');
        });
    }
}
