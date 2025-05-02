<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'English',      'code' => 'en'],
            ['name' => 'Italian',      'code' => 'it'],
            ['name' => 'Spanish',      'code' => 'es'],
            ['name' => 'French',       'code' => 'fr'],
            ['name' => 'German',       'code' => 'de'],
            ['name' => 'Japanese',     'code' => 'ja'],
            ['name' => 'Portuguese',   'code' => 'pt'],
            ['name' => 'Chinese',      'code' => 'zh'],
            ['name' => 'Arabic',       'code' => 'ar'],
            ['name' => 'Russian',      'code' => 'ru'],
            ['name' => 'Hindi',        'code' => 'hi'],
            ['name' => 'Korean',       'code' => 'ko'],
            ['name' => 'Turkish',      'code' => 'tr'],
            ['name' => 'Dutch',        'code' => 'nl'],
            ['name' => 'Polish',       'code' => 'pl'],
            ['name' => 'Greek',        'code' => 'el'],
            ['name' => 'Swedish',      'code' => 'sv'],
            ['name' => 'Norwegian',    'code' => 'no'],
            ['name' => 'Finnish',      'code' => 'fi'],
            ['name' => 'Czech',        'code' => 'cs'],
        ];

        foreach ($languages as $language) {
            Language::create($language);
        }
    }
}
