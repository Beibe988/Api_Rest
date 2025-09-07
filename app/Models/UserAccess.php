<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $table = 'User_Access';
    protected $fillable = ['id_user','ip','user_agent','last_seen_at','hits'];
    protected $casts    = ['last_seen_at'=>'datetime','hits'=>'integer'];
}
