<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',50);
            $table->enum('type',['Public','Private']);
            $table->integer('category_id')->unsigned();
            $table->integer('difficulty_level')->unsigned();
            $table->tinyInteger('maximum_participant')->unsigned();
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
            $table->timestamps();
            $table->foreign('category_id')
                   ->references('id')->on('course_categories')
                   ->onDelete('cascade');
            $table->foreign('difficulty_level')
                    ->references('id')->on('instructor_levels')
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
        Schema::dropIfExists('courses');
    }
}
