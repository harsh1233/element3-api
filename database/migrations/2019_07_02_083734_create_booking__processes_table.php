<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_processes', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('booking_process_course_detail_id')->unsigned();
            $table->integer('booking_process_customer_detail_id')->unsigned();
            $table->integer('booking_process_instructor_detail_id')->unsigned();
            $table->integer('booking_process_payment_detail_id')->unsigned();
            $table->string('note',200);
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
             $table->foreign('booking_process_course_detail_id')
                   ->references('id')->on('booking_process_course_details')
                   ->onDelete('cascade');
            $table->foreign('booking_process_customer_detail_id')
                   ->references('id')->on('booking_process_customer_details')
                   ->onDelete('cascade');
            $table->foreign('booking_process_instructor_detail_id')
                   ->references('id')->on('booking_process_instructor_details')
                   ->onDelete('cascade');
            $table->foreign('booking_process_payment_detail_id')
                   ->references('id')->on('booking_process_payment_details')
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
        Schema::dropIfExists('booking_processes');
    }
}
