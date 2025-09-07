<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $languages = [
            ['name' => 'English',   'code' => 'en'],
            ['name' => 'Italian',   'code' => 'it'],
            ['name' => 'Spanish',   'code' => 'es'],
            ['name' => 'French',    'code' => 'fr'],
            ['name' => 'German',    'code' => 'de'],
            ['name' => 'Japanese',  'code' => 'ja'],
            ['name' => 'Portuguese','code' => 'pt'],
            ['name' => 'Chinese',   'code' => 'zh'],
            ['name' => 'Arabic',    'code' => 'ar'],
            ['name' => 'Russian',   'code' => 'ru'],
            ['name' => 'Hindi',     'code' => 'hi'],
            ['name' => 'Korean',    'code' => 'ko'],
            ['name' => 'Turkish',   'code' => 'tr'],
            ['name' => 'Dutch',     'code' => 'nl'],
            ['name' => 'Polish',    'code' => 'pl'],
            ['name' => 'Greek',     'code' => 'el'],
            ['name' => 'Swedish',   'code' => 'sv'],
            ['name' => 'Norwegian', 'code' => 'no'],
            ['name' => 'Finnish',   'code' => 'fi'],
            ['name' => 'Czech',     'code' => 'cs'],
        ];

        // Inserisce/aggiorna in modo idempotente e SOLO per colonne realmente esistenti
        $cols = Schema::getColumnListing('languages');

        foreach ($languages as $lang) {
            $payload = array_intersect_key($lang, array_flip($cols));
            if (in_array('updated_at', $cols)) $payload['updated_at'] = $now;
            if (in_array('created_at', $cols)) $payload['created_at'] = $now;

            DB::table('languages')->updateOrInsert(
                ['code' => $lang['code']], // chiave unica
                $payload                   // aggiorna name/timestamps se già esiste
            );
        }
    }
}

