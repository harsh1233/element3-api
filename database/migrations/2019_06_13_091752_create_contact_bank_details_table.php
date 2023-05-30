<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactBankDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_bank_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('iban_no',50);
            $table->string('account_no',50)->nullable();
            $table->string('bank_name',50);
            $table->integer('salary_group')->unsigned();
            $table->date('joining_date')->nullable();
            $table->date('last_booking_date')->nullable();
            $table->foreign('salary_group')
                   ->references('id')->on('salary_groups')
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
        Schema::dropIfExists('contact_bank_details');
    }
}
