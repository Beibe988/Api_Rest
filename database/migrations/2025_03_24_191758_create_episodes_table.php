<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('serie_tv_id');
            $table->string('title');
            $table->integer('season');
            $table->integer('episode_number');
            $table->text('description')->nullable();
            $table->string('video_link')->nullable();
            $table->string('language')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Chi ha inserito l'episodio
            $table->timestamps();

            $table->foreign('serie_tv_id')->references('id')->on('serie_tv')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};

