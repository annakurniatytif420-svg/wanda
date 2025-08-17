<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageUploadService;

class CustomerAuthController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function register(Request $request)
    {
        try {
            // Handle both JSON and form data
            $name = $request->input('name') ?? $request->name;
            $email = $request->input('email') ?? $request->email;
            $phone = $request->input('phone') ?? $request->phone;
            $password = $request->input('password') ?? $request->password;

            $request->validate([
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users',
                'phone'    => 'nullable|string',
                'password' => 'required|min:6|confirmed'
            ], [
                'name.required' => 'Name is required',
                'email.required' => 'Email is required',
                'email.email' => 'Email must be a valid email address',
                'email.unique' => 'Email already exists',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 6 characters',
                'password.confirmed' => 'Password confirmation does not match'
            ]);

            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => Hash::make($password),
                'role'     => 'customer',
            ]);

            // Create customer profile
            $profileData = [
                'user_id' => $user->id,
                'address' => $request->input('address') ?? $request->address ?? null,
                'skin_tone' => $request->input('skin_tone') ?? $request->skin_tone ?? null,
                'skin_type' => $request->input('skin_type') ?? $request->skin_type ?? null,
                'skincare_history' => $request->input('skincare_history') ?? $request->skincare_history ?? null,
                'allergies' => $request->input('allergies') ?? $request->allergies ?? null,
                'makeup_preferences' => $request->input('makeup_preferences') ?? $request->makeup_preferences ?? null,
                'skin_issues' => $request->input('skin_issues') ?? $request->skin_issues ?? null,
            ];

            // Handle JSON fields properly
            $jsonFields = ['skin_type', 'makeup_preferences'];
            foreach ($jsonFields as $field) {
                if (isset($profileData[$field])) {
                    // If it's already a string, check if it's JSON
                    if (is_string($profileData[$field])) {
                        $decoded = json_decode($profileData[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $profileData[$field] = $decoded;
                        } else {
                            // If not JSON, treat as comma-separated
                            $profileData[$field] = array_filter(array_map('trim', explode(',', $profileData[$field])));
                        }
                    }
                    // If it's an array, keep as is
                }
            }

            // Ensure empty arrays are properly handled
            foreach ($jsonFields as $field) {
                if (isset($profileData[$field]) && is_array($profileData[$field]) && empty($profileData[$field])) {
                    $profileData[$field] = [];
                }
            }

            // Handle profile photo upload
            $profilePhotoUrl = null;
            if ($request->hasFile('profile_photo')) {
                $filename = $this->imageUploadService->uploadProfilePhoto($request->file('profile_photo'));
                $profileData['profile_photo'] = $filename;
                $profilePhotoUrl = $this->imageUploadService->getImageUrl($filename, 'images/profile_photos');
            }

            // Create the profile
            $profile = CustomerProfile::create($profileData);

            // Add profile photo URL to response
            $profileArray = $profile->toArray();
            $profileArray['profile_photo_url'] = $profilePhotoUrl;

            return response()->json([
                'message' => 'Customer registered successfully',
                'user' => $user,
                'profile' => $profileArray
            ], 201);
        } catch (\Throwable $e) {
            // \Log::error('Customer REGISTER ERROR', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString(),
            //     'request_data' => $request->all()
            // ]);
            return response()->json([
                'message' => 'Failed to register Customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role !== 'customer') {
                Auth::logout();
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user
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
