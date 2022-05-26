<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUsersAddDisputeCounters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_delivery_count')
                ->default(0)
                ->comment('Количество неудачных доставок')
                ->after('reviews_count');

            $table->unsignedTinyInteger('failed_receive_count')
                ->default(0)
                ->comment('Количество неудачных получений')
                ->after('failed_delivery_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('failed_delivery_count');
            $table->dropColumn('failed_receive_count');
        });
    }
}
