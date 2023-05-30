<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalaryGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',50);
            $table->string('description',250);
            $table->enum('salary_type',['F','H'])->comment("F=Fixed H=Hourly");
            $table->float('amount', 8, 2);
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
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
        Schema::dropIfExists('salary_groups');
    }
}
