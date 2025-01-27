<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id');
            $table->string('name');
            $table->boolean('active')->default(0);
            $table->string('location')->nullable();
            $table->boolean('for_follow_up')->default(0);
            $table->double('salary');
            $table->string('video')->nullable();
            $table->mediumText('description')->nullable();
            $table->integer('industry_id');
            $table->integer('role_id');
            $table->integer('expire_days')->nullable();
            $table->timestamp('expire_date')->nullable();
            $table->timestamp('start_at')->nullable();
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
        Schema::dropIfExists('jobs');
    }
}
