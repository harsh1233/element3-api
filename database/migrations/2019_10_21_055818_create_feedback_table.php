<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('instructor_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->integer('booking_id')->unsigned();
            $table->integer('course_id')->unsigned();
            $table->date('course_taken_date');
            $table->decimal('average_rating',2,1);
            $table->string('final_comment',512)->nullable();
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('customer_id')
                   ->references('id')->on('users')
                   ->onDelete('cascade');
            $table->foreign('instructor_id')
                   ->references('id')->on('users')
                   ->onDelete('cascade');
            $table->foreign('booking_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->foreign('course_id')
                   ->references('id')->on('courses')
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
        Schema::dropIfExists('feedback');
    }
}
