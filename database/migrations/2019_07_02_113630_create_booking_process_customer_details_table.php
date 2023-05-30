<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingProcessCustomerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_process_customer_details', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('booking_process_id')->unsigned();
            $table->integer('customer_id')->unsigned()->comment("Private=contact_id, Group=group_id");
            $table->string('additional_participant',50)->nullable();
            $table->string('accommodation',50)->nullable();
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
        Schema::dropIfExists('booking_process_customer_details');
    }
}
