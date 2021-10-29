<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPointsFieldToUserStatisticsDailyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_statistics_daily', function (Blueprint $table) {
            $table->decimal('points',16,8)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_statistics_daily', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
}
