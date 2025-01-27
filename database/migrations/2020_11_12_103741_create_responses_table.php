<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->string('status')->default('INVITED');
            $table->string('full')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('text')->nullable();
            $table->string('location')->nullable();
            $table->boolean('invited')->default(0);
            $table->tinyInteger('rating')->nullable();
            $table->text('note')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('hash');
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
        Schema::dropIfExists('responses');
    }
}
