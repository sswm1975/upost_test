<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUsersRefactoringRating extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['creator_rating', 'freelancer_rating']);
            $table->unsignedInteger('scores_count')->default(0)->after('wallet')->comment('Количество баллов');
            $table->unsignedInteger('reviews_count')->default(0)->after('wallet')->comment('Количество отзывов');
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
            //
        });
    }
}
