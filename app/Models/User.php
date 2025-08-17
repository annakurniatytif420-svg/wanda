<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['profile_photo_url'];

    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->muaProfile || $this->muaProfile->profile_photo === null) {
            $defaultAvatar = 'default-avatar.jpg';
            $supabaseBaseUrl = env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images');

            return $supabaseBaseUrl . '/' . $defaultAvatar;
        }
        $supabaseBaseUrl = rtrim(env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images'), '/') . '/profile_photos';
        return $supabaseBaseUrl . '/' . ltrim($this->muaProfile->attributes['profile_photo'], '/');
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function muaProfile()
    {
        return $this->hasOne(MuaProfile::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'mua_id');
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class, 'mua_id');
    }

    public function bookingsAsCustomer()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function bookingsAsMua()
    {
        return $this->hasMany(Booking::class, 'mua_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }
}
