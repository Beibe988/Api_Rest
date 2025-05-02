<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerieTv extends Model
{
    use HasFactory;

    protected $table = 'serie_tv';

    protected $fillable = ['title', 'year', 'description', 'category', 'language', 'user_id'];

    public function episodes()
    {
        return $this->hasMany(Episode::class, 'serie_tv_id');
    }

}


