<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RulesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('Rules')->updateOrInsert(
            ['rule_key' => 'max_login_attempts'],
            ['rule_value' => '3', 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
