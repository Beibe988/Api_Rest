<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'year',
        'video_link',
        'category',
        'description',
        'language',
        'user_id' // Assicurati che sia presente nel database
    ];

    /**
     * Relazione con l'utente che ha creato il film.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}



