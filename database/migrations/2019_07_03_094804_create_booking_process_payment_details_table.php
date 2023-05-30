<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingProcessPaymentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_process_payment_details', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('booking_process_id')->unsigned();
            $table->integer('payment_method_id')->unsigned();
            $table->string('payment_link',191);
            $table->float('total_price', 8, 2);
            $table->float('extra_participant', 8, 2)->nullable()->unsigned();
            $table->float('discount', 8, 2)->nullable()->unsigned();
            $table->float('net_price', 8, 2)->unsigned();
            $table->foreign('booking_process_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->foreign('payment_method_id')
                   ->references('id')->on('payment_methods')
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
        Schema::dropIfExists('booking_process_payment_details');
    }
}
