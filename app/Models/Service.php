<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'mua_id',
        'name',
        'description',
        'price',
        'duration',
        'photo',
        'makeup_style',
        'category'
    ];
    protected $appends = ['formatted_price', 'service_photo_url'];

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getServicePhotoUrlAttribute()
    {
        if ($this->attributes['photo']) {
            $supabaseBaseUrl = rtrim(env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images'), '/') . '/service_photos';
            return $supabaseBaseUrl . '/' . ltrim($this->attributes['photo'], '/');
        }

        $defaultAvatar = 'default-service.jpeg';
        $supabaseBaseUrl = env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images');

        return $supabaseBaseUrl . '/' . $defaultAvatar;
    }
}
