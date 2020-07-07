<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_routes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('data_type_id')->unique();
	        $table->string('name');
	        $table->string('title');
	        $table->string('sub_title')->nullable();
	        $table->text('body')->nullable();
            $table->string('slug');
	        $table->string('slug_field')->nullable();
	        $table->string('controller_name');
            $table->string('template'); // {$template}.index | {$template}.child
	        $table->integer("order")->nullable()->default(1);
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
        Schema::dropIfExists('data_routes');
    }
}
