<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStructureOfNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropMorphs('notifiable');
            $table->renameColumn('data', 'payload');
            $table->dropColumn('read_at');

            // New Columns
            $table->id()->first();

            $table->after('type', function ($table) {
                $table->string('scope');
                $table->morphs('entity');

                $table->unsignedBigInteger('sender_id')->nullable();
                $table->foreign('sender_id')
                    ->references('id')
                    ->on('users');
            });

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {

            $table->morphs('notifiable');
            $table->renameColumn('payload', 'data');
            $table->timestamp('read_at')->nullable();

            $table->dropPrimary();
            $table->dropColumn('id');
            $table->dropColumn('scope');
            $table->dropMorphs('entity');

            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
        });
    }
}
