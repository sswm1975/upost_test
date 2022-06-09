<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddViewedInRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->unsignedTinyInteger('viewed_by_performer')
                ->default(0)
                ->after('is_read')
                ->comment('Подтвержденная ставка просмотрена исполнителем');

            $table->renameColumn('is_read', 'viewed_by_customer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('viewed_by_performer');
            $table->renameColumn('viewed_by_customer', 'is_read');
        });
    }
}
