<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });


        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('content')->nullable();
            $table->foreignId('author_id' )
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('content');
            $table->foreignId('account_id' )
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            $table->foreignId('post_id' )
                ->references('id')
                ->on('posts')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('comments');
    }

};