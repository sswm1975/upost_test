<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutePointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_points', function (Blueprint $table) {
            $table->increments('id')->comment('Код');
            $table->unsignedInteger('route_id')->comment('Код маршрута');
            $table->unsignedInteger('country_id')->comment('Код страны');
            $table->unsignedInteger('city_id')->comment('Код города');
            $table->date('date')->comment('Дата нахождения');

            $table->foreign('route_id')
                ->references('route_id')->on('routes')
                ->onDelete('cascade');
        });

        DB::statement("ALTER TABLE route_points COMMENT = 'Смежные точки маршрута'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_points');
    }
}
