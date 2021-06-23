<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdInUsersChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_change', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('users_change', function (Blueprint $table) {
            $table->increments('users_change_id')->first();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_change', function (Blueprint $table) {
            //
        });
    }
}
