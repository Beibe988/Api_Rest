<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Risolvi nome tabella profili (case-insensitive tra user_profiles / User_profiles)
        $profilesTable = null;
        if (Schema::hasTable('user_profiles')) {
            $profilesTable = 'user_profiles';
        } elseif (Schema::hasTable('User_profiles')) {
            $profilesTable = 'User_profiles';
        }

        if (!$profilesTable) {
            return; // nessuna tabella profili: usciamo in modo idempotente
        }

        // 2) Assicurati che la tabella profili abbia i campi necessari
        Schema::table($profilesTable, function (Blueprint $table) use ($profilesTable) {
            $addCol = function (string $col, callable $def) use ($table, $profilesTable) {
                if (!Schema::hasColumn($profilesTable, $col)) {
                    $def($table);
                }
            };

            $addCol('first_name', fn($t) => $t->string('first_name', 255)->nullable());
            $addCol('last_name', fn($t) => $t->string('last_name', 255)->nullable());
            $addCol('display_name', fn($t) => $t->string('display_name', 255)->nullable());
            $addCol('birth_date', fn($t) => $t->date('birth_date')->nullable());
            $addCol('birth_city', fn($t) => $t->string('birth_city', 128)->nullable());
            $addCol('birth_province', fn($t) => $t->string('birth_province', 16)->nullable());
            $addCol('gender', fn($t) => $t->string('gender', 1)->nullable()); // 'M' | 'F'
            $addCol('fiscal_code', fn($t) => $t->string('fiscal_code', 16)->nullable());
            $addCol('locale', fn($t) => $t->string('locale', 8)->nullable());
            $addCol('timezone', fn($t) => $t->string('timezone', 64)->nullable());
        });

        // 2b) Indici/unique solo se assenti
        $this->ensureIndex($profilesTable, 'fiscal_code', 'user_profiles_fiscal_code_idx');
        if (Schema::hasColumn($profilesTable, 'id_user')) {
            $this->ensureUnique($profilesTable, 'id_user', 'user_profiles_id_user_unique');
        }

        // 3) BACKFILL: crea profili mancanti e copia dati da users
        $colsUsers = Schema::getColumnListing('users');

        $hasBirthDate      = in_array('birth_date', $colsUsers, true);
        $hasBirthCity      = in_array('birth_city', $colsUsers, true);
        $hasBirthProvince  = in_array('birth_province', $colsUsers, true);
        $hasGender         = in_array('gender', $colsUsers, true);
        $hasFiscalCode     = in_array('fiscal_code', $colsUsers, true);
        $hasDisplayNameU   = in_array('display_name', $colsUsers, true);
        $hasStreet         = in_array('street', $colsUsers, true);
        $hasCity           = in_array('city', $colsUsers, true);
        $hasProvince       = in_array('province', $colsUsers, true);
        $hasPostalCode     = in_array('postal_code', $colsUsers, true);
        $hasCountry        = in_array('country', $colsUsers, true);
        $hasLocaleU        = in_array('locale', $colsUsers, true);
        $hasTimezoneU      = in_array('timezone', $colsUsers, true);

        // indirizzo sul profilo solo se il profilo ha le colonne
        $colsProfiles = Schema::getColumnListing($profilesTable);
        $canAddrStreet    = in_array('street', $colsProfiles, true);
        $canAddrCity      = in_array('city', $colsProfiles, true);
        $canAddrProvince  = in_array('province', $colsProfiles, true);
        $canAddrPostal    = in_array('postal_code', $colsProfiles, true);
        $canAddrCountry   = in_array('country', $colsProfiles, true);

        DB::table('users')->orderBy('id')->chunk(500, function ($users) use (
            $profilesTable,
            $hasBirthDate, $hasBirthCity, $hasBirthProvince, $hasGender, $hasFiscalCode, $hasDisplayNameU,
            $hasStreet, $hasCity, $hasProvince, $hasPostalCode, $hasCountry, $hasLocaleU, $hasTimezoneU,
            $canAddrStreet, $canAddrCity, $canAddrProvince, $canAddrPostal, $canAddrCountry
        ) {
            foreach ($users as $u) {
                // assicurati che esista la riga di profilo
                $profile = DB::table($profilesTable)->where('id_user', $u->id)->first();
                if (!$profile) {
                    DB::table($profilesTable)->insert([
                        'id_user'    => $u->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $profile = DB::table($profilesTable)->where('id_user', $u->id)->first();
                }

                $payload = ['updated_at' => now()];

                // prova a decifrare name/surname in first_name/last_name
                $payload['first_name'] = $this->safeDecrypt($u->name);
                $payload['last_name']  = $this->safeDecrypt($u->surname);

                if ($hasDisplayNameU && !empty($u->display_name)) $payload['display_name'] = $u->display_name;

                if ($hasBirthDate     && !empty($u->birth_date))     $payload['birth_date']     = $u->birth_date;
                if ($hasBirthCity     && !empty($u->birth_city))     $payload['birth_city']     = $u->birth_city;
                if ($hasBirthProvince && !empty($u->birth_province)) $payload['birth_province'] = $u->birth_province;
                if ($hasGender        && !empty($u->gender))         $payload['gender']         = $u->gender;
                if ($hasFiscalCode    && !empty($u->fiscal_code))    $payload['fiscal_code']    = strtoupper(trim($u->fiscal_code));

                if ($hasLocaleU   && !empty($u->locale))   $payload['locale']   = $u->locale;
                if ($hasTimezoneU && !empty($u->timezone)) $payload['timezone'] = $u->timezone;

                if ($canAddrStreet  && $hasStreet     && isset($u->street))      $payload['street']      = $u->street;
                if ($canAddrCity    && $hasCity       && isset($u->city))        $payload['city']        = $u->city;
                if ($canAddrProvince&& $hasProvince   && isset($u->province))    $payload['province']    = $u->province;
                if ($canAddrPostal  && $hasPostalCode && isset($u->postal_code)) $payload['postal_code'] = $u->postal_code;
                if ($canAddrCountry && $hasCountry    && isset($u->country))     $payload['country']     = $u->country;

                // rimuovi null/undefined ridondanti per evitare update inutili
                $clean = array_filter($payload, fn($v) => !is_null($v));
                if (!empty($clean)) {
                    DB::table($profilesTable)->where('id', $profile->id)->update($clean);
                }
            }
        });

        // 4) Drop colonne errate da users (se presenti)
        Schema::table('users', function (Blueprint $table) use (
            $hasBirthDate, $hasBirthCity, $hasBirthProvince, $hasGender, $hasFiscalCode, $hasDisplayNameU,
            $hasStreet, $hasCity, $hasProvince, $hasPostalCode, $hasCountry, $hasLocaleU, $hasTimezoneU
        ) {
            $drop = function (string $col) use ($table) {
                try { $table->dropColumn($col); } catch (\Throwable $e) {}
            };

            if ($hasBirthDate)     $drop('birth_date');
            if ($hasBirthCity)     $drop('birth_city');
            if ($hasBirthProvince) $drop('birth_province');
            if ($hasGender)        $drop('gender');
            if ($hasFiscalCode)    $drop('fiscal_code');
            if ($hasDisplayNameU)  $drop('display_name');

            if ($hasStreet)        $drop('street');
            if ($hasCity)          $drop('city');
            if ($hasProvince)      $drop('province');
            if ($hasPostalCode)    $drop('postal_code');
            if ($hasCountry)       $drop('country');

            if ($hasLocaleU)       $drop('locale');
            if ($hasTimezoneU)     $drop('timezone');
        });
    }

    public function down(): void
    {
        // Rimuove i campi aggiunti al profilo e indici (non ripristina i campi in users)
        $profilesTable = null;
        if (Schema::hasTable('user_profiles')) {
            $profilesTable = 'user_profiles';
        } elseif (Schema::hasTable('User_profiles')) {
            $profilesTable = 'User_profiles';
        }
        if (!$profilesTable) return;

        // Drop indici se presenti
        $this->dropIndexIfExists($profilesTable, 'user_profiles_fiscal_code_idx');
        $this->dropUniqueIfExists($profilesTable, 'user_profiles_id_user_unique');

        Schema::table($profilesTable, function (Blueprint $table) use ($profilesTable) {
            $dropIf = function (string $col) use ($table, $profilesTable) {
                if (Schema::hasColumn($profilesTable, $col)) {
                    try { $table->dropColumn($col); } catch (\Throwable $e) {}
                }
            };
            $dropIf('first_name');
            $dropIf('last_name');
            $dropIf('display_name');
            $dropIf('birth_date');
            $dropIf('birth_city');
            $dropIf('birth_province');
            $dropIf('gender');
            $dropIf('fiscal_code');
            $dropIf('locale');
            $dropIf('timezone');
        });
    }

    /** -------- Helpers indici senza Doctrine -------- */

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::table('information_schema.statistics')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->first();
        return (bool) $row;
    }

    private function anyIndexOnColumn(string $table, string $column): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::table('information_schema.statistics')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first();
        return (bool) $row;
    }

    private function ensureIndex(string $table, string $column, string $indexName): void
    {
        // se esiste un qualsiasi indice su quella colonna, non creare un duplicato
        if ($this->indexExists($table, $indexName) || $this->anyIndexOnColumn($table, $column)) {
            return;
        }
        Schema::table($table, function (Blueprint $t) use ($column, $indexName) {
            try { $t->index($column, $indexName); } catch (\Throwable $e) {}
        });
    }

    private function ensureUnique(string $table, string $column, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }
        Schema::table($table, function (Blueprint $t) use ($column, $indexName) {
            try { $t->unique($column, $indexName); } catch (\Throwable $e) {}
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) return;
        Schema::table($table, function (Blueprint $t) use ($indexName) {
            try { $t->dropIndex($indexName); } catch (\Throwable $e) {}
        });
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) return;
        Schema::table($table, function (Blueprint $t) use ($indexName) {
            try { $t->dropUnique($indexName); } catch (\Throwable $e) {}
        });
    }

    /** decifra in sicurezza; se fallisce restituisce valore invariato */
    private function safeDecrypt($v)
    {
        if ($v === null) return null;
        try { return Crypt::decryptString($v); } catch (\Throwable $e) { return $v; }
    }
};

