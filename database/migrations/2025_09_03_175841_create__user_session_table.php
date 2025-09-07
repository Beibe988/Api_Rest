<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('User_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users','id')->cascadeOnDelete();

            // Token della sessione applicativa (es. fingerprint del token Sanctum)
            $table->string('token', 255)->index();

            // Nomi campi in italiano come richiesto
            $table->timestamp('inizio_sessione');
            $table->timestamp('fine_sessione')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('User_session');
    }
};
