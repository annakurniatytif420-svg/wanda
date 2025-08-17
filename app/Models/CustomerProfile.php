<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id', 'skin_tone', 'skin_type', 'skin_issues', 'address',
        'skincare_history', 'allergies', 'makeup_preferences', 'profile_photo'
    ];

    protected $casts = [
        'skin_type' => 'array',
        'makeup_preferences' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Ensure JSON fields are properly handled when setting
    public function setSkinTypeAttribute($value)
    {
        if (is_array($value)) {
            // Handle empty arrays
            if (empty($value)) {
                $this->attributes['skin_type'] = '[]';
            } else {
                $this->attributes['skin_type'] = json_encode($value);
            }
        } elseif (is_string($value)) {
            // Check if it's already JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->attributes['skin_type'] = $value;
            } else {
                // If not JSON, encode it as JSON
                $this->attributes['skin_type'] = json_encode($value);
            }
        } else {
            $this->attributes['skin_type'] = $value;
        }
    }
    
    // Ensure JSON fields are properly handled when setting
    public function setMakeupPreferencesAttribute($value)
    {
        if (is_array($value)) {
            // Handle empty arrays
            if (empty($value)) {
                $this->attributes['makeup_preferences'] = '[]';
            } else {
                $this->attributes['makeup_preferences'] = json_encode($value);
            }
        } elseif (is_string($value)) {
            // Check if it's already JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->attributes['makeup_preferences'] = $value;
            } else {
                // If not JSON, encode it as JSON
                $this->attributes['makeup_preferences'] = json_encode($value);
            }
        } else {
            $this->attributes['makeup_preferences'] = $value;
        }
    }
}
