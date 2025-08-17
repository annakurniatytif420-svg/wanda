<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MuaProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'certification',
        'service_area',
        'studio_lat',
        'studio_lng',
        'makeup_styles',
        'makeup_specializations',
        'available_days',
        'available_start_time',
        'available_end_time',
        'profile_photo',
        'skin_type'
    ];

    protected $casts = [
        'makeup_styles' => 'array',
        'makeup_specializations' => 'array',
        'skin_type' => 'array',
        'available_days' => 'array',
        'certification' => 'array',
    ];

    protected $appends = ['profile_photo_url'];

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->attributes['profile_photo']) {
            $supabaseBaseUrl = rtrim(env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images'), '/') . '/profile_photos';
            return $supabaseBaseUrl . '/' . ltrim($this->attributes['profile_photo'], '/');
        }

        $defaultAvatar = 'default-avatar.jpg';
        $supabaseBaseUrl = env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images');

        return $supabaseBaseUrl . '/' . $defaultAvatar;
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
