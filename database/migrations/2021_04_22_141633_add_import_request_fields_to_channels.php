<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportRequestFieldsToChannels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('youtube_channel_url')->nullable()->after('points');
            $table->string('youtube_channel_id')->nullable()->after('points');
            $table->boolean('is_import_requested')->nullable()->after('points')->default(0);
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
            $table->dropColumn('youtube_channel_url');
            $table->dropColumn('youtube_channel_id');
            $table->dropColumn('is_import_requested');
        });
    }
}
