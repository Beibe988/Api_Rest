<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\LanguageSeeder::class,
            \Database\Seeders\RulesTableSeeder::class,
        ]);
    }
}
