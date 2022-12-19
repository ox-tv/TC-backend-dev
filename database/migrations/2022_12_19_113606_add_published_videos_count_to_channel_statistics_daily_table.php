<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublishedVideosCountToChannelStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_statistics_daily', function (Blueprint $table) {
            $table->integer('published_videos')->default(0);
            $table->integer('unpublished_videos')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_statistics_daily', function (Blueprint $table) {
            $table->dropColumn('published_videos');
            $table->dropColumn('unpublished_videos');
        });
    }
}
