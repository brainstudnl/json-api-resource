<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestModel extends Migration
{
    public function up(): void
    {
        Schema::create('test_models', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('title');
            $table->string('description')->nullable();
            $table->integer('relation_a_id')->nullable();
            $table->integer('test_model_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_models');
    }

};