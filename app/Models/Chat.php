<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['booking_id', 'sender_id', 'message', 'read'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
