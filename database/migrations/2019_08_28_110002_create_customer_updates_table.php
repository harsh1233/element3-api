<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_updates', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('instructor_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->string('discription',500);
            $table->string('url',191);
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
        Schema::dropIfExists('customer_updates');
    }
}
