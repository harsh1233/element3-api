<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->enum('salutation',['Mr.','Mrs.','Ms.','Dr.','Jr.']);
            $table->string('first_name',50);
            $table->string('middle_name',50)->nullable();
            $table->string('last_name',50)->nullable();
            $table->string('email');
            $table->string('mobile1',15)->nullable();
            $table->string('mobile2',15)->nullable();
            $table->string('nationality',50);
            $table->string('designation',50)->nullable();
            $table->date('dob')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender',['M','F','O'])->comment("M=Male, F=Female, O=Other");
            $table->string('profile_pic')->nullable();
            $table->boolean('display_in_app')->default(false);
            $table->foreign('category_id')
                   ->references('id')->on('categories')
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
        Schema::dropIfExists('contacts');
    }
}
