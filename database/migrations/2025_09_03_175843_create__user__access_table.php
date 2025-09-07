<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('User_Access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users','id')->cascadeOnDelete();

            $table->string('ip', 45);              // IPv4/IPv6
            $table->string('user_agent', 255)->nullable(); // opzionale ma utile
            $table->timestamp('last_seen_at')->useCurrent(); // utile per aggregazioni
            $table->unsignedInteger('hits')->default(1);

            $table->timestamps();

            $table->unique(['id_user','ip','user_agent']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('User_Access');
    }
};
