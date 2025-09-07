<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('User_auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users','id')->cascadeOnDelete()->unique();

            // Email hashata (HMAC-SHA256 consigliato) e secret per uso applicativo (NON il token Sanctum)
            $table->char('email_hash', 64)->unique();
            $table->string('secret_jwt_token')->nullable();

            // Per lockout: tentativi falliti e timestamp di blocco
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('User_auth');
    }
};

