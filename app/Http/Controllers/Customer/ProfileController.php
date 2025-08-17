<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function show()
    {
        try {
            $user = Auth::user();

            // Cek apakah CustomerProfile sudah ada dengan relasi user
            $profile = CustomerProfile::with('user')->where('user_id', $user->id)->first();

            if (!$profile) {
                // Buat CustomerProfile baru jika belum ada
                $profile = CustomerProfile::create([
                    'user_id' => $user->id,
                    'skin_tone' => '',
                    'skin_type' => [],
                    'skin_issues' => '',
                    'address' => '',
                    'skincare_history' => '',
                    'allergies' => '',
                    'makeup_preferences' => [],
                    'profile_photo' => null
                ]);

                Log::info('CustomerProfile created for user: ' . $user->id);
            }

            // Add S3 URL for profile photo
            if ($profile->profile_photo) {
                $profile->profile_photo_url = $this->imageUploadService->getImageUrl($profile->profile_photo, 'images/profile_photos');
            } else {
                $profile->profile_photo_url = null;
            }

            // Tambahkan data user ke response
            $profileData = $profile->toArray();
            $profileData['name'] = $profile->user->name ?? null;
            $profileData['email'] = $profile->user->email ?? null;
            $profileData['phone'] = $profile->user->phone ?? null;

            return response()->json([
                'success' => true,
                'profile' => $profileData,
                'message' => 'Profile data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving customer profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = Auth::user();
            $profile = CustomerProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            $validated = $request->validate([
                'address' => 'nullable|string',
                'skin_tone' => 'nullable|string',
                'skin_type' => 'nullable|string',
                'skin_issues' => 'nullable|string',
                'skincare_history' => 'nullable|string',
                'allergies' => 'nullable|string',
                'makeup_preferences' => 'nullable|string',
                'profile_photo' => 'nullable|image|max:5048',
            ]);

            // Handle profile photo upload to S3
            if ($request->hasFile('profile_photo')) {
                // Delete old photo if exists
                if ($profile->profile_photo) {
                    $this->imageUploadService->deleteImage($profile->profile_photo, 'images/profile_photos');
                }

                $filename = $this->imageUploadService->uploadProfilePhoto($request->file('profile_photo'));
                $validated['profile_photo'] = $filename;
            }

            // Handle JSON fields
            $jsonFields = ['skin_type', 'makeup_preferences'];
            foreach ($jsonFields as $field) {
                if (isset($validated[$field]) && !empty($validated[$field])) {
                    $decoded = json_decode($validated[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $validated[$field] = $decoded;
                    } else {
                        $validated[$field] = array_filter(array_map('trim', explode(',', $validated[$field])));
                    }
                } else {
                    $validated[$field] = [];
                }
            }

            $profile->update($validated);

            // Refresh profile with photo URL
            if ($profile->profile_photo) {
                $profile->profile_photo_url = $this->imageUploadService->getImageUrl($profile->profile_photo, 'images/profile_photos');
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'profile' => $profile
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating customer profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
