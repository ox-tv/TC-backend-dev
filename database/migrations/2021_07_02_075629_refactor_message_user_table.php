<?php

use App\Models\MessageUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorMessageUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //$old_data = MessageUser::all()->toArray();
        Schema::rename('message_user', 'message_user_old');

        Schema::create('message_user', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('message_id')
                ->references('id')
                ->on('messages');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->tinyInteger('status')->default(1);

            $table->unique(['message_id', 'user_id']);
        });

//        $old_data  = json_encode($old_data);
//        $old_data = json_decode($old_data, true);
//        MessageUser::insert($old_data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //$old_data = MessageUser::all()->toArray();
        Schema::rename('message_user', 'message_user_old2');
        Schema::rename('message_user_old', 'message_user');
        Schema::rename('message_user_old2', 'message_user_old');
        //MessageUser::truncate();
        //MessageUser::insert($old_data);
    }
}
