<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingProcessInstructorDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_process_instructor_details', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('booking_process_id')->unsigned();
            $table->integer('contact_id')->unsigned();
            $table->foreign('booking_process_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->foreign('contact_id')
                   ->references('id')->on('contacts')
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
        Schema::dropIfExists('booking_process_instructor_details');
    }
}
