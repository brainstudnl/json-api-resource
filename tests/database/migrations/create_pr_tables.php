<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('pull_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('developer_id')
                ->references('id')
                ->on('developers')
                ->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->text('content');
            $table->foreignId('reviewer_id')
                ->references('id')
                ->on('developers')
                ->onDelete('cascade');

            $table->foreignId('pull_request_id')
                ->references('id')
                ->on('pull_requests')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('developers');
        Schema::dropIfExists('pull_requests');
        Schema::dropIfExists('reviews');
    }
};
