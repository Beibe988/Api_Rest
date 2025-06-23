<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

   protected $fillable = [
        'name',
        'surname',
        'birth_year',
        'country',
        'language',
        'email',
        'role',
        'credits',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'credits' => 'integer',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function getNameAttribute($value)
    {
        return \Crypt::decryptString($value);
    }

    public function getSurnameAttribute($value)
    {
        return \Crypt::decryptString($value);
    }

    public function getEmailAttribute($value)
    {
        return \Crypt::decryptString($value);
    }

}


