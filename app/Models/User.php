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
        // anagrafica base
        'name', 'surname', 'email',
        'birth_year', 'birth_city', 'birth_province',
        // contatti/lingua
        'country', 'language',
        // ruoli/crediti
        'role', 'credits',
        // CF cifrato
        'fiscal_code',
        // hash (li settiamo via mutator; opzionale tenerli nei fillable)
        'hash_name', 'hash_surname', 'hash_email', 'hash_fiscal_code',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'credits' => 'integer',
    ];

    /* -------------------------------------------
     | Helpers
     ------------------------------------------- */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    private static function enc(?string $v): ?string
    {
        if ($v === null || $v === '') return null;
        return \Crypt::encryptString($v);
    }

    private static function dec(?string $v): ?string
    {
        if ($v === null || $v === '') return null;
        try { return \Crypt::decryptString($v); } catch (\Throwable) { return $v; }
    }

    private static function hashStr(?string $v): ?string
    {
        if ($v === null) return null;
        return hash('sha256', strtolower(trim($v)));
    }

    /* -------------------------------------------
     | Accessors (decifrano)
     ------------------------------------------- */
    public function getNameAttribute($value)        { return self::dec($value); }
    public function getSurnameAttribute($value)     { return self::dec($value); }
    public function getEmailAttribute($value)       { return self::dec($value); }
    public function getFiscalCodeAttribute($value)  { return self::dec($value); }

    /* -------------------------------------------
     | Mutators (cifran + hash)
     ------------------------------------------- */
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = self::enc($value);
        $this->attributes['hash_name'] = self::hashStr($value);
    }

    public function setSurnameAttribute($value): void
    {
        $this->attributes['surname'] = self::enc($value);
        $this->attributes['hash_surname'] = self::hashStr($value);
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = self::enc($value);
        $this->attributes['hash_email'] = self::hashStr($value);
    }

    public function setFiscalCodeAttribute($value): void
    {
        $value = $value ? strtoupper(trim($value)) : null;
        $this->attributes['fiscal_code'] = self::enc($value);
        $this->attributes['hash_fiscal_code'] = self::hashStr($value);
    }

    /* -------------------------------------------
     | Relazioni (come già avevi)
     ------------------------------------------- */
    public function passwordRow()
    {
        return $this->hasOne(\App\Models\UserPassword::class, 'id_user');
    }

    public function accesses()
    {
        return $this->hasMany(\App\Models\UserAccess::class, 'id_user');
    }
}


