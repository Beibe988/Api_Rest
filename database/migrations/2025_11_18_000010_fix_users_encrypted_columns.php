<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Portiamo le colonne cifrate a TEXT (il criptato base64 supera 255 char)
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->text('name')->nullable(false)->change();
            }
            if (Schema::hasColumn('users', 'surname')) {
                $table->text('surname')->nullable(false)->change();
            }
            if (Schema::hasColumn('users', 'email')) {
                $table->text('email')->nullable(false)->change();
            }

            // hash_email/hash_name/hash_surname: idealmente CHAR(64). Se già esistono, li lasciamo così;
            // opzionale: $table->char('hash_email', 64)->change(); ecc.
        });

        // 2) Assicuriamoci che la unique ricada su hash_email (non su email cifrata)
        //    Rimuoviamo la unique su email se esiste.
        try {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_email_unique`');
        } catch (\Throwable $e) {
            // indice non presente o nome diverso: ignora
        }

        // 3) Unique su hash_email (se non già unica)
        //    Prima verifichiamo se esiste già un indice unico su hash_email
        $hasUniqueOnHashEmail = false;
        try {
            $indexes = DB::select('SHOW INDEX FROM `users`');
            foreach ($indexes as $idx) {
                if (($idx->Column_name ?? null) === 'hash_email' && (int)($idx->Non_unique ?? 1) === 0) {
                    $hasUniqueOnHashEmail = true;
                    break;
                }
            }
        } catch (\Throwable $e) {}

        if (!$hasUniqueOnHashEmail && Schema::hasColumn('users', 'hash_email')) {
            try {
                DB::statement('ALTER TABLE `users` ADD UNIQUE `users_hash_email_unique` (`hash_email`)');
            } catch (\Throwable $e) {
                // se fallisce perché l'indice esiste con altro nome, pace.
            }
        }
    }

    public function down(): void
    {
        // Ripristino "best effort": riportiamo a VARCHAR(255) e togliamo la unique su hash_email.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name', 255)->nullable(false)->change();
            }
            if (Schema::hasColumn('users', 'surname')) {
                $table->string('surname', 255)->nullable(false)->change();
            }
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email', 255)->nullable(false)->change();
            }
        });

        try {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_hash_email_unique`');
        } catch (\Throwable $e) {}
    }
};
