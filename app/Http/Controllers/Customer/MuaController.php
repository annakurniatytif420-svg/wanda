<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MuaProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MuaController extends Controller
{

    public function index()
    {
        $muas = MuaProfile::with(['user', 'services'])
            ->where('is_active', true)
            ->whereHas('user', function($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->map(function ($mua) {
                return [
                    'id' => $mua->id,
                    'user' => [
                        'id' => $mua->user->id,
                        'name' => $mua->user->name,
                        'email' => $mua->user->email,
                    ],
                    'location' => $mua->location,
                    'specialization' => $mua->specialization,
                    'starting_price' => $mua->starting_price,
                    'average_rating' => $mua->average_rating,
                    'review_count' => $mua->reviews_count,
                    'profile_photo_url' => $mua->profile_photo_url,
                    'specializations' => $mua->specializations,
                    'services' => $mua->services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'description' => $service->description,
                    'price' => $service->price,
                    'duration' => $service->duration,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'muas' => $muas,
            'total' => $muas->count(),
            'has_more' => false,
        ]);
    }
}