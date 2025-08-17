<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = ['customer_id', 'mua_id'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }
}
