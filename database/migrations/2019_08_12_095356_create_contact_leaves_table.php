<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_leaves', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contact_id')->unsigned();
            $table->integer('leave_id')->unsigned();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('no_of_days')->unsigned();
            $table->string('reason',255);
            $table->enum('leave_status',['1','2'])->comment('1=Approve, 2=Disapprove');
            $table->enum('is_paid',['Y','N'])->comment('Y=Yes, N=No');
            $table->boolean('is_active')->default(true);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('contact_id')
                   ->references('id')->on('contacts')
                   ->onDelete('cascade');
            $table->foreign('leave_id')
                   ->references('id')->on('leaves')
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
        Schema::dropIfExists('contact_leaves');
    }
}
