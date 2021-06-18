<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \Doctrine\DBAL\Driver\PDO\MySQL\Driver;

class FixCreatorAndAddFieldTypeToReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->addColumn('boolean', 'review_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('user_creater_rating', 'user_creator_rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('review_type');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('user_creator_rating', 'user_creater_rating');
        });
    }
}
