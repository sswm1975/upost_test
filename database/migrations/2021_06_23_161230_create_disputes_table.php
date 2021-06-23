<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->increments('dispute_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('problem_id');
            $table->json('files');
            $table->text('comment');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE disputes COMMENT = "Спори"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disputes');
    }
}
