<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id')->comment('Код');
            $table->unsignedInteger('rate_id')->comment('Код ставки');
            $table->enum('job_status', ['active', 'work', 'dispute', 'successful', 'done'])->default('active')->comment('Статус');
        });

        DB::statement('ALTER TABLE jobs COMMENT = "Завдання"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
