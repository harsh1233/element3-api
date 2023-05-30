<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstructorActivityTimesheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instructor_activity_timesheets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('instructor_id')->unsigned()->comment("Refer to users table");
            $table->integer('booking_id')->unsigned()->comment("Refer to booking_processes table");
            $table->date('activity_date');
            $table->time('start_time')->comment("store activity start time");
            $table->time('end_time')->default("00:00:00")->comment("store activity end time");
            $table->time('current_time')->comment("store current time when record created or updated");
            $table->enum('status',['P','IP','A','R'])->default("P")->comment('P=Pending, IP=In Progress, A=Approved, R=Rejected');
            $table->time('total_activity_hours')->default("00:00:00");
            $table->time('total_break_hours')->default("00:00:00");
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
        Schema::dropIfExists('instructor_activity_timesheets');
    }
}
