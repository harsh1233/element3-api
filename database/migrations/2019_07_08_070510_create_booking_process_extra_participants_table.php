<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookingProcessExtraParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_process_extra_participants', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('booking_process_id')->unsigned();
            $table->integer('contact_id')->unsigned();
            $table->foreign('contact_id')
                   ->references('id')->on('contacts')
                   ->onDelete('cascade');
            $table->foreign('booking_process_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
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
        Schema::dropIfExists('booking_process_extra_participants');
    }
}
