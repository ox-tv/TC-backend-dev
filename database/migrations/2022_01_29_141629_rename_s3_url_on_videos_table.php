<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameS3UrlOnVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Add a new column with the regular name:
        Schema::table('videos', function(Blueprint $table)
        {
            $table->text('file_url')->after('file_path')->nullable();
        });

        //Copy the data across to the new column:
        DB::table('videos')->update([
            'file_url' => DB::raw('s3_url')
        ]);

        //Remove the old column:
        Schema::table('videos', function(Blueprint $table)
        {
            $table->dropColumn('s3_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Add a new column with the regular name:
        Schema::table('videos', function(Blueprint $table)
        {
            $table->text('s3_url')->after('file_path')->nullable();
        });

        //Copy the data across to the new column:
        DB::table('videos')->update([
            's3_url' => DB::raw('file_url')
        ]);

        //Remove the old column:
        Schema::table('videos', function(Blueprint $table)
        {
            $table->dropColumn('file_url');
        });
    }
}
