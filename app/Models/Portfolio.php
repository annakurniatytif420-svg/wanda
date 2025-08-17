<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $fillable = [
        'mua_id', 'media_type', 'media_url', 'caption'
    ];

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }
}
