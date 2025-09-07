<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('User_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users', 'id')->cascadeOnDelete()->unique();

            // Campi profilo (come da proposta precedente)
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('locale', 10)->default('it');
            $table->string('timezone', 64)->default('Europe/Rome');
            $table->date('birthdate')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('bio')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('User_profiles');
    }
};
