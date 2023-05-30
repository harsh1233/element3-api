<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMountainSkiLiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mountain_ski_lifts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',50);
            $table->integer('mountain_id')->unsigned()->comment("Refer to mountains table");
            $table->foreign('mountain_id')
                   ->references('id')->on('mountains')
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
        Schema::dropIfExists('mountain_ski_lifts');
    }
}
