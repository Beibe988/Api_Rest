<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dati di nascita
            $table->string('birth_city', 255)->nullable()->after('birth_year');
            $table->string('birth_province', 2)->nullable()->after('birth_city');

            // Codice Fiscale: cifrato + hash per univocità/ricerca
            $table->string('fiscal_code', 255)->nullable()->after('birth_province');
            $table->string('hash_fiscal_code', 64)->nullable()->after('fiscal_code');

            // Indici utili
            $table->index('hash_fiscal_code', 'users_hash_fiscal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_hash_fiscal_idx');
            $table->dropColumn(['birth_city', 'birth_province', 'fiscal_code', 'hash_fiscal_code']);
        });
    }
};
