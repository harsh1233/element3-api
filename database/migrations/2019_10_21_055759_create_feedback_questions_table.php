<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('question',512);
            $table->string('question_de',512)->nullable();
            $table->boolean('is_under_eighteen')->default(false)->comment("Check if feedback question is in under 18 question or not");
            $table->boolean('is_element3')->default(false)->comment("Check if feedback question is for element3 or not");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feedback_questions');
    }
}
