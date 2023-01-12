<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRespondentInDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->unsignedInteger('respondent_id')->comment('Код пользователя ответчика')->after('user_id');

            $table->foreign('respondent_id', 'FK_disputes_respondent_users')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn('respondent_id');
        });
    }
}
