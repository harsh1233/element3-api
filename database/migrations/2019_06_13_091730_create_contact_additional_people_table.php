<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactAdditionalPeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_additional_people', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contact_id')->unsigned();
            $table->foreign('contact_id')
                   ->references('id')->on('contacts')
                   ->onDelete('cascade');
            $table->enum('salutation',['Mr.','Mrs.','Ms.','Dr.','Jr.']);       
            $table->string('name',50);
            $table->string('relationaship',50)->nullable();
            $table->string('mobile1',15)->nullable();
            $table->string('mobile2',15)->nullable();
            $table->string('comments',500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_additional_people');
    }
}
