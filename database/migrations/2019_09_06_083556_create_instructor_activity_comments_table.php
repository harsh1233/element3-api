<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstructorActivityCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instructor_activity_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('booking_id')->unsigned()->comment("Refer to booking_processes table");
            $table->enum('comment_by',['I','C'])->comment('I=Instructor, C=Customer');
            $table->integer('comment_user_id')->unsigned()->comment("Refer to users table");
            $table->string('description',255);
            $table->date('comment_date');
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('booking_id')
                   ->references('id')->on('booking_processes')
                   ->onDelete('cascade');
            $table->foreign('comment_user_id')
                   ->references('id')->on('users')
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
        Schema::dropIfExists('instructor_activity_comments');
    }
}
