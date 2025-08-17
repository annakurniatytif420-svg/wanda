<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlockedTimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'mua_id',
        'date',
        'full_day',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'full_day' => 'boolean',
    ];

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }
}
