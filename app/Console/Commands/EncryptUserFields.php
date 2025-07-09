<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class EncryptUserFields extends Command
{
    protected $signature = 'users:encrypt-fields';
    protected $description = 'Cripta e aggiorna name, surname, email già presenti e genera hash associati';

    public function handle()
    {
        $users = DB::table('users')->get();
        $encrypted = 0;

        foreach ($users as $user) {
            // Controlla che i dati siano ancora in chiaro (basic check)
            // Se name contiene già un payload JSON di Crypt, non toccare
            $isNameEncrypted = false;
            try {
                // Se va in errore, NON è già criptato (e quindi va criptato)
                $try = json_decode($user->name, true);
                if (
                    is_array($try) &&
                    isset($try['iv']) &&
                    isset($try['value']) &&
                    isset($try['mac'])
                ) {
                    $isNameEncrypted = true;
                }
            } catch (\Exception $e) {
                $isNameEncrypted = false;
            }

            if ($isNameEncrypted) {
                // Già criptato, salta!
                continue;
            }

            try {
                DB::table('users')->where('id', $user->id)->update([
                    'name'         => Crypt::encryptString($user->name),
                    'surname'      => Crypt::encryptString($user->surname),
                    'email'        => Crypt::encryptString($user->email),
                    'hash_name'    => hash('sha256', strtolower(trim($user->name))),
                    'hash_surname' => hash('sha256', strtolower(trim($user->surname))),
                    'hash_email'   => hash('sha256', strtolower(trim($user->email))),
                ]);
                $encrypted++;
            } catch (\Exception $e) {
                $this->error("Errore su user ID {$user->id}: " . $e->getMessage());
            }
        }

        $this->info("Campi criptati per $encrypted utenti.");
        return 0;
    }
}







