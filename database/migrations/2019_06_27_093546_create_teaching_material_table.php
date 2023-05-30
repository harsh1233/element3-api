<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeachingMaterialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teaching_material', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('teaching_material_category_id')->unsigned();
            $table->string('name',50);
            $table->enum('formate',['Video','Audio','Pdf','Photo']);
            $table->string('url',191);
            $table->boolean('is_active')->default(true);
            $table->foreign('teaching_material_category_id')
                   ->references('id')->on('teaching_material_categories')
                   ->onDelete('cascade');
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
        Schema::dropIfExists('teaching_material');
    }
}
