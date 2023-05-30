<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',100);
            $table->string('street_address1',250);
            $table->string('street_address2',250);
            $table->string('city',50);
            $table->string('state',50)->nullable();
            $table->integer('country')->unsigned();
            $table->foreign('country')
                   ->references('id')->on('countries')
                   ->onDelete('cascade');
            $table->boolean('is_head_office')->default(false);
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
        Schema::dropIfExists('offices');
    }
}
