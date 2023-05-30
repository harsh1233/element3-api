<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCourseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->unsigned();
            $table->enum('session',['High Seasion','Low Seasion'])->nullable();
            $table->enum('time',['Morning','Afternoon'])->nullable();
            $table->tinyInteger('price_per_day')->unsigned()->nullable();
            $table->tinyInteger('hours_per_day')->unsigned()->nullable();
            $table->tinyInteger('extra_person_charge')->unsigned()->nullable();
            $table->timestamps();
            $table->foreign('course_id')
                   ->references('id')->on('courses')
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
        Schema::dropIfExists('course_details');
    }
}
