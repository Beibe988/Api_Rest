<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('User_psw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users','id')->cascadeOnDelete()->unique();

            // Password hashata + sale (oltre al sale interno dell'algoritmo)
            $table->string('sale', 64);
            $table->string('psw_hash', 255);
            $table->enum('algo', ['argon2id','bcrypt'])->default('argon2id');
            $table->timestamp('password_updated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('User_psw');
    }
};


