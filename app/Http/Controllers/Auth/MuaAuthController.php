<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MuaProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MuaAuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|min:6|confirmed',
                'bio' => 'nullable|string|max:1000',
                'certification' => 'nullable|array',
                'certification.*' => 'string|max:255',
                'service_area' => 'nullable|string|max:500',
                'available_days' => 'nullable|array',
                'available_days.*' => 'string|max:10',
                'available_start_time' => 'nullable',
                'available_end_time' => 'nullable',
                'makeup_specializations' => 'nullable|array',
                'makeup_specializations.*' => 'string|max:100',
                'makeup_styles' => 'nullable|array',
                'makeup_styles.*' => 'string|max:100',
                'skin_type' => 'nullable|array',
                'skin_type.*' => 'string|max:50',
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            // Handle profile photo upload
            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('mua_profiles', 'public');
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'mua',
            ]);

            // Create MUA profile
            $muaProfile = MuaProfile::create([
                'user_id' => $user->id,
                'bio' => $request->bio ?? '',
                'certification' => json_encode($request->certification ?? []),
                'service_area' => $request->service_area ?? '',
                'available_days' => json_encode($request->available_days ?? []),
                'available_start_time' => $request->available_start_time ?? null,
                'available_end_time' => $request->available_end_time ?? null,
                'makeup_specializations' => json_encode($request->makeup_specializations ?? []),
                'makeup_styles' => json_encode($request->makeup_styles ?? []),
                'skin_type' => json_encode($request->skin_type ?? []),
                'profile_photo' => $profilePhotoPath,
            ]);

            return response()->json([
                'message' => 'MUA registered successfully',
                'user' => $user,
                'profile' => $muaProfile
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('MUA REGISTER ERROR', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to register MUA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role !== 'mua') {
                Auth::logout();
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'profile' => $user->muaProfile
            ]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
