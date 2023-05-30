<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_details', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('account_id')->unsigned();
            $table->integer('booking_process_id')->unsigned();
            $table->enum('transaction_type',['CR','DB'])->comment("CR=Credit DB=Debit");
            $table->float('amount', 8, 2);
            $table->foreign('booking_process_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->foreign('account_id')
                   ->references('id')->on('account_masters')
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
        Schema::dropIfExists('account_details');
    }
}
