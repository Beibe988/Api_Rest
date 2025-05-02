<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('serie_tv', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->year('year');
        $table->string('category');
        $table->string('language');
        $table->text('description')->nullable();
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serie_tvs');
    }
};
