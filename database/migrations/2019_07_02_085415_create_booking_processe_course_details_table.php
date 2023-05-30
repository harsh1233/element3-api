<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingProcesseCourseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_process_course_details', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('booking_process_id')->unsigned();
            $table->integer('course_id')->unsigned();
            $table->enum('course_type',['Private','Group']);
            $table->integer('course_id')->unsigned();
            $table->integer('course_detail_id')->unsigned();
            $table->dateTime('StartDate_Time');
            $table->dateTime('EndDate_Time');
            $table->integer('lead')->unsigned();
            $table->integer('contact_id')->unsigned();
            $table->integer('source_id')->unsigned();
            $table->integer('no_of_instructor')->unsigned();
            $table->integer('no_of_participant')->unsigned();
            $table->string('meeting_point',100);
            $table->double('meeting_point_lat');
            $table->double('meeting_point_long');
            $table->foreign('booking_process_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->foreign('source_id')
                   ->references('id')->on('booking_process_sources')
                   ->onDelete('cascade');
            $table->foreign('contact_id')
                   ->references('id')->on('contacts')
                   ->onDelete('cascade');
            $table->foreign('lead')
                   ->references('id')->on('categories')
                   ->onDelete('cascade');
            $table->foreign('course_id')
                   ->references('id')->on('courses')
                   ->onDelete('cascade');
            $table->foreign('course_detail_id')
                   ->references('id')->on('course_details')
                   ->onDelete('cascade');       
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking_process_course_details');
    }
}
