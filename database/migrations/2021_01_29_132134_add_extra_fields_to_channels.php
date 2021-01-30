<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToChannels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('slogan')->after('intro_video_id')->nullable();
            $table->string('website')->after('intro_video_id')->nullable();
            $table->string('twitter')->after('intro_video_id')->nullable();
            $table->string('facebook')->after('intro_video_id')->nullable();
            $table->string('instagram')->after('intro_video_id')->nullable();
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
            $table->dropColumn('slogan');
            $table->dropColumn('website');
            $table->dropColumn('twitter');
            $table->dropColumn('facebook');
            $table->dropColumn('instagram');
        });
    }
}
