<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('feedback_id')->unsigned();
            $table->integer('question_id')->unsigned();
            $table->boolean('is_element3')->default(false)->comment("Check if given feeback question is for element3 or not.");
            $table->decimal('rating',2,1);
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('feedback_id')
                   ->references('id')->on('feedback')
                   ->onDelete('cascade');
            $table->foreign('question_id')
                   ->references('id')->on('feedback_questions')
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
        Schema::dropIfExists('feedback_details');
    }
}
