<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTodoActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('todo_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('todo_id')->unsigned()->comment("Refer to todos table");
            $table->enum('action_type',['created','updated','deleted','done','assiged']);
            $table->integer('action_by')->unsigned()->comment("Refer to users table");
            $table->integer('assiged_to')->nullable()->unsigned()->comment("Refer to users table");
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
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
        Schema::dropIfExists('todo_actions');
    }
}
