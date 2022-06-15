<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsTo2faTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('2fa', function (Blueprint $table) {

            $table->string('ip')->after('user_id')->nullable();
            $table->unsignedTinyInteger('app_type')->nullable()->after('app_status');

            $table->timestamp('app_verified_at')->after('app_secret')->nullable();
            $table->timestamp('email_verified_at')->after('email_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('2fa', function (Blueprint $table) {
            //
        });
    }
}
