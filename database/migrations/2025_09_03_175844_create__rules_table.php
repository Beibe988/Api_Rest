<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Rules', function (Blueprint $table) {
            $table->id();

            // Evito la parola riservata "key" come nome colonna
            $table->string('rule_key')->unique();
            $table->string('rule_value');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('Rules');
    }
};

