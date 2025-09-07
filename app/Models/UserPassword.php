<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPassword extends Model
{
    protected $table = 'User_password'; // nome esatto (case-sensitive su Linux)
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Elenco largo: verranno usati solo i campi realmente presenti a DB
    protected $fillable = [
        'id_user',
        'sale',            // opzionale
        'psw_hash',        // opzionale
        'password',        // opzionale (schema legacy)
        'algo',            // opzionale
        'password_updated_at', // opzionale
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_user');
    }
}
