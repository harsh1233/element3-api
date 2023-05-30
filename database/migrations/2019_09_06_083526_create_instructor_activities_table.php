<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstructorActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instructor_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('instructor_id')->unsigned()->comment("Refer to users table");
            $table->integer('booking_id')->unsigned()->comment("Refer to booking_processes table");
            $table->enum('activity_type',['AS','AE','BS','BE'])->comment('AS=Activity Start, AE=Activity End, BS=Break Start, BE=Break End');
            $table->date('activity_date');
            $table->time('activity_time');
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('instructor_id')
                   ->references('id')->on('users')
                   ->onDelete('cascade');
            $table->foreign('booking_id')
                   ->references('id')->on('booking_processes')
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
        Schema::dropIfExists('instructor_activities');
    }
}
