<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactDifficultyLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_difficulty_levels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contact_id')->unsigned()->comment("Refer to contacts table");
            $table->integer('difficulty_level_id')->unsigned()->comment("Refer to instructor levels table");
            $table->softDeletes();
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
        Schema::dropIfExists('contact_difficulty_levels');
    }
}
