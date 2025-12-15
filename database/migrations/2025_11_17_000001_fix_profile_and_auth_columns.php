<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * USER_PROFILES: aggiunta/normalizzazione campi anagrafici
         * NB: manteniamo nullable per compatibilità con dati esistenti.
         * L'obbligatorietà è fatta a livello di validation nel controller.
         */
        if (Schema::hasTable('User_profiles')) {
            Schema::table('User_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('User_profiles', 'first_name')) {
                    $table->string('first_name', 255)->nullable()->after('id_user');
                }
                if (!Schema::hasColumn('User_profiles', 'last_name')) {
                    $table->string('last_name', 255)->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('User_profiles', 'display_name')) {
                    $table->string('display_name', 255)->nullable()->after('last_name');
                }
                if (!Schema::hasColumn('User_profiles', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('display_name');
                }
                if (!Schema::hasColumn('User_profiles', 'birth_city')) {
                    $table->string('birth_city', 128)->nullable()->after('birth_date');
                }
                if (!Schema::hasColumn('User_profiles', 'birth_province')) {
                    $table->string('birth_province', 16)->nullable()->after('birth_city');
                }
                if (!Schema::hasColumn('User_profiles', 'gender')) {
                    // puoi usare enum(['M','F']) se preferisci, string(1) è più flessibile
                    $table->string('gender', 1)->nullable()->after('birth_province'); // 'M' | 'F'
                }
                if (!Schema::hasColumn('User_profiles', 'fiscal_code')) {
                    $table->string('fiscal_code', 16)->nullable()->after('gender');
                    $table->index('fiscal_code', 'user_profiles_fiscal_code_idx');
                }
                if (!Schema::hasColumn('User_profiles', 'locale')) {
                    $table->string('locale', 8)->nullable()->after('fiscal_code');
                }
                if (!Schema::hasColumn('User_profiles', 'timezone')) {
                    $table->string('timezone', 64)->nullable()->after('locale');
                }
                // assicurati che esista l'indice/unique 1:1 su id_user
                $indexes = $this->listIndexes('User_profiles');
                if (!in_array('user_profiles_id_user_unique', $indexes, true)) {
                    try { $table->unique('id_user', 'user_profiles_id_user_unique'); } catch (\Throwable $e) {}
                }
            });
        }

        /**
         * USER_PASSWORDS: garantire struttura base
         */
        if (Schema::hasTable('user_passwords')) {
            Schema::table('user_passwords', function (Blueprint $table) {
                if (!Schema::hasColumn('user_passwords', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->after('id');
                }
                if (!Schema::hasColumn('user_passwords', 'password_hash')) {
                    $table->string('password_hash', 128)->after('user_id');
                }
                if (!Schema::hasColumn('user_passwords', 'salt')) {
                    $table->string('salt', 128)->after('password_hash');
                }
                if (!Schema::hasColumn('user_passwords', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('user_passwords', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
                // indici utili
                $indexes = $this->listIndexes('user_passwords');
                if (!in_array('user_passwords_user_id_unique', $indexes, true)) {
                    try { $table->unique('user_id', 'user_passwords_user_id_unique'); } catch (\Throwable $e) {}
                }
            });
        }

        /**
         * USER_LOGIN_DATA: secret_jwt resta qui
         */
        if (Schema::hasTable('user_login_data')) {
            Schema::table('user_login_data', function (Blueprint $table) {
                if (!Schema::hasColumn('user_login_data', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->after('id');
                }
                if (!Schema::hasColumn('user_login_data', 'secret_jwt')) {
                    $table->string('secret_jwt', 128)->after('user_id');
                }
                if (!Schema::hasColumn('user_login_data', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('user_login_data', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
                // indici utili
                $indexes = $this->listIndexes('user_login_data');
                if (!in_array('user_login_data_user_id_unique', $indexes, true)) {
                    try { $table->unique('user_id', 'user_login_data_user_id_unique'); } catch (\Throwable $e) {}
                }
            });
        }

        /**
         * USER_AUTH: fingerprint + tentativi
         * (non spostiamo secret_jwt qui)
         */
        if (Schema::hasTable('User_auth')) {
            Schema::table('User_auth', function (Blueprint $table) {
                if (!Schema::hasColumn('User_auth', 'id_user')) {
                    $table->unsignedBigInteger('id_user')->after('id');
                }
                if (!Schema::hasColumn('User_auth', 'email_hash')) {
                    $table->string('email_hash', 64)->nullable()->after('id_user');
                    $table->index('email_hash', 'user_auth_email_hash_idx');
                }
                if (!Schema::hasColumn('User_auth', 'secret_jwt_token')) {
                    // fingerprint del token (sha256 del jwt) opzionale
                    $table->string('secret_jwt_token', 128)->nullable()->after('email_hash');
                }
                if (!Schema::hasColumn('User_auth', 'failed_attempts')) {
                    $table->unsignedTinyInteger('failed_attempts')->default(0)->after('secret_jwt_token');
                }
                if (!Schema::hasColumn('User_auth', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable()->after('failed_attempts');
                }
                if (!Schema::hasColumn('User_auth', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('User_auth', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
                // 1:1 con utente
                $indexes = $this->listIndexes('User_auth');
                if (!in_array('user_auth_id_user_unique', $indexes, true)) {
                    try { $table->unique('id_user', 'user_auth_id_user_unique'); } catch (\Throwable $e) {}
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('User_profiles')) {
            Schema::table('User_profiles', function (Blueprint $table) {
                foreach ([
                    'first_name','last_name','display_name','birth_date',
                    'birth_city','birth_province','gender','fiscal_code',
                    'locale','timezone',
                ] as $col) {
                    if (Schema::hasColumn('User_profiles', $col)) {
                        $table->dropColumn($col);
                    }
                }
                $indexes = $this->listIndexes('User_profiles');
                if (in_array('user_profiles_fiscal_code_idx', $indexes, true)) {
                    try { $table->dropIndex('user_profiles_fiscal_code_idx'); } catch (\Throwable $e) {}
                }
                if (in_array('user_profiles_id_user_unique', $indexes, true)) {
                    try { $table->dropUnique('user_profiles_id_user_unique'); } catch (\Throwable $e) {}
                }
            });
        }

        if (Schema::hasTable('user_passwords')) {
            Schema::table('user_passwords', function (Blueprint $table) {
                $indexes = $this->listIndexes('user_passwords');
                if (in_array('user_passwords_user_id_unique', $indexes, true)) {
                    try { $table->dropUnique('user_passwords_user_id_unique'); } catch (\Throwable $e) {}
                }
                foreach (['user_id','password_hash','salt','created_at','updated_at'] as $col) {
                    // Non rimuovo user_id/password di default per non rompere lo schema esistente
                    // Se vuoi proprio fare rollback "puro", decommenta qui sotto:
                    // if (Schema::hasColumn('user_passwords', $col)) $table->dropColumn($col);
                }
            });
        }

        if (Schema::hasTable('user_login_data')) {
            Schema::table('user_login_data', function (Blueprint $table) {
                $indexes = $this->listIndexes('user_login_data');
                if (in_array('user_login_data_user_id_unique', $indexes, true)) {
                    try { $table->dropUnique('user_login_data_user_id_unique'); } catch (\Throwable $e) {}
                }
                foreach (['user_id','secret_jwt','created_at','updated_at'] as $col) {
                    // come sopra, non rimuovo per sicurezza
                }
            });
        }

        if (Schema::hasTable('User_auth')) {
            Schema::table('User_auth', function (Blueprint $table) {
                $indexes = $this->listIndexes('User_auth');
                if (in_array('user_auth_email_hash_idx', $indexes, true)) {
                    try { $table->dropIndex('user_auth_email_hash_idx'); } catch (\Throwable $e) {}
                }
                if (in_array('user_auth_id_user_unique', $indexes, true)) {
                    try { $table->dropUnique('user_auth_id_user_unique'); } catch (\Throwable $e) {}
                }
                foreach (['id_user','email_hash','secret_jwt_token','failed_attempts','locked_at','created_at','updated_at'] as $col) {
                    // non rimuovo per non perdere dati, se vuoi rollback completo decommenta:
                    // if (Schema::hasColumn('User_auth', $col)) $table->dropColumn($col);
                }
            });
        }
    }

    /**
     * Utility: legge i nomi indice della tabella.
     * Nota: non è standard su tutte le piattaforme, ma funziona bene su MySQL/MariaDB.
     */
    private function listIndexes(string $table): array
    {
        try {
            $connection = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $connection->listTableIndexes($table);
            return array_map(fn($i) => $i->getName(), $indexes);
        } catch (\Throwable $e) {
            // Se Doctrine DBAL non è installato, torniamo un array vuoto
            return [];
        }
    }
};
