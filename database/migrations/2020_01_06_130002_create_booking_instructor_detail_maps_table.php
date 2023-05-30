<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingInstructorDetailMapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_instructor_detail_maps', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('contact_id')->unsigned();
            $table->integer('booking_process_id')->unsigned();
            $table->dateTime('startdate_time');
            $table->dateTime('enddate_time');
            $table->foreign('contact_id')
                   ->references('id')->on('contacts')
                   ->onDelete('cascade');
            $table->foreign('booking_process_id')
                   ->references('id')->on('booking_processes')
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
        Schema::dropIfExists('booking_instructor_detail_maps');
    }
}
