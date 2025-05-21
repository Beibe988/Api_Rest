<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Per ogni utente che non ha ancora una riga in user_login_data, aggiungi un record
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $exists = DB::table('user_login_data')->where('user_id', $user->id)->exists();

            if (!$exists) {
                DB::table('user_login_data')->insert([
                    'user_id'    => $user->id,
                    'secret_jwt' => bin2hex(random_bytes(32)),
                    'created_at' => now(),
                ]);
            }
        }
    }
    public function down(): void
    {
        Schema::table('user_login_data', function (Blueprint $table) {
            //
        });
    }
};
