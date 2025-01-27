<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanStripesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_stripes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(1);
            $table->text('prices');
            $table->integer('users_limit');
            $table->integer('interviews_limit');
            $table->integer('responses_limit');
            $table->tinyInteger('branding');
            $table->tinyInteger('email_invites');
            $table->tinyInteger('sms_invites');
            $table->tinyInteger('bulk_invites');
            $table->tinyInteger('questions_databases');
            $table->tinyInteger('export');
            $table->integer('companies_limit');
            $table->tinyInteger('zapier');
            $table->tinyInteger('api');
            $table->tinyInteger('live');
            $table->tinyInteger('ai_assist');
            $table->text('description')->nullable();
            $table->string('stripe_name')->nullable();
            $table->tinyInteger('extra');
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
        Schema::dropIfExists('plan_stripes');
    }
}
