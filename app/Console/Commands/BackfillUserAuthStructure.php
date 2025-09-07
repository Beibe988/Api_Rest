<?php

namespace App\Console\Commands;

use App\Support\AuthHasher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillUserAuthStructure extends Command
{
    protected $signature = 'users:backfill-auth {--chunk=200} {--dry-run}';
    protected $description = 'Crea/riempie User_profiles e User_auth. NON tocca User_password (già popolata). Se manca il record password, prova un inserimento minimo.';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $dry   = (bool) $this->option('dry-run');

        $count = DB::table('users')->count();
        $this->info("Users totali: {$count}. Backfill (chunk={$chunk}, dry-run=" . ($dry ? 'S' : 'N') . ")");

        $hasUserPassword = Schema::hasTable('User_password');
        $colsUserPassword = $hasUserPassword ? Schema::getColumnListing('User_password') : [];

        DB::table('users')->orderBy('id')->chunkById($chunk, function ($users) use ($dry, $hasUserPassword, $colsUserPassword) {
            $now = now();

            foreach ($users as $u) {
                // 1) User_profiles
                $existsProfile = DB::table('User_profiles')->where('id_user', $u->id)->exists();
                if (!$existsProfile) {
                    $payload = [
                        'id_user'      => $u->id,
                        'display_name' => $u->name ?? null,
                        'locale'       => 'it',
                        'timezone'     => 'Europe/Rome',
                        'created_at'   => $now, 'updated_at' => $now,
                    ];
                    if ($dry) $this->line("[DRY] insert User_profiles user={$u->id}");
                    else DB::table('User_profiles')->insert($payload);
                }

                // 2) User_auth
                $existsAuth = DB::table('User_auth')->where('id_user', $u->id)->exists();
                if (!$existsAuth) {
                    $email  = (string) ($u->email ?? '');
                    $emailHash = $email !== '' ? AuthHasher::emailHash($email) : bin2hex(random_bytes(32));
                    $payload = [
                        'id_user'         => $u->id,
                        'email_hash'      => $emailHash,
                        'secret_jwt_token'=> null,
                        'failed_attempts' => 0,
                        'locked_at'       => null,
                        'created_at'      => $now, 'updated_at' => $now,
                    ];
                    if ($dry) $this->line("[DRY] insert User_auth user={$u->id}");
                    else DB::table('User_auth')->insert($payload);
                }

                // 3) User_password (SOLO se manca il record, con inserimento minimo best-effort)
                if ($hasUserPassword) {
                    $existsPwd = DB::table('User_password')->where('id_user', $u->id)->exists();
                    if (!$existsPwd) {
                        // Tenta un inserimento minimale copiando l'hash legacy da users.password
                        $payload = ['id_user' => $u->id, 'created_at'=>$now, 'updated_at'=>$now];

                        if (in_array('psw_hash', $colsUserPassword)) {
                            $payload['psw_hash'] = (string) ($u->password ?? '');
                        } elseif (in_array('password', $colsUserPassword)) {
                            $payload['password'] = (string) ($u->password ?? '');
                        }
                        if (in_array('sale', $colsUserPassword)) $payload['sale'] = '';
                        if (in_array('algo', $colsUserPassword)) $payload['algo'] = str_starts_with((string)$u->password, '$argon2id$') ? 'argon2id' : 'bcrypt';
                        if (in_array('password_updated_at', $colsUserPassword)) $payload['password_updated_at'] = $now;

                        if (count($payload) > 2) { // abbiamo almeno id_user + un campo utile
                            if ($dry) $this->line("[DRY] insert User_password user={$u->id}");
                            else DB::table('User_password')->insert($payload);
                        } else {
                            $this->warn("User_password schema troppo diverso: salto user {$u->id}");
                        }
                    }
                }
            }
        });

        $this->info('Backfill completato.');
        return self::SUCCESS;
    }
}
