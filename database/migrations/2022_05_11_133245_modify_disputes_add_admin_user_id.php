<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDisputesAddAdminUserId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->unsignedInteger('admin_user_id')->nullable()->comment('Менеджер закрепленный за спором');
            $table->foreign('admin_user_id', 'FK_disputes_admin_users')->references('id')->on('admin_users')->onDelete('cascade');
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
            $table->dropColumn('admin_user_id');
        });
    }
}
