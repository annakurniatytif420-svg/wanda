<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MeController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->with([
                'customerProfile',
                'muaProfile',
                'services',
                'portfolios',
                'bookingsAsCustomer',
                'bookingsAsMua',
                'wishlists',
                'profile_photo'
            ])
            ->get();

        return response()->json($users);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user()->load([
            'customerProfile',
            'muaProfile',
            'services',
            'portfolios',
            'bookingsAsCustomer',
            'bookingsAsMua',
            'wishlists',
        ]);

        return response()->json($user);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $isDirty = false;

        if ($request->has('name') && $user->name !== $request->input('name')) {
            $user->name = $request->input('name');
            $isDirty = true;
        }

        if ($request->has('email') && $user->email !== $request->input('email')) {
            $user->email = $request->input('email');
            $isDirty = true;
        }

        if ($request->has('phone') && $user->phone !== $request->input('phone')) {
            $user->phone = $request->input('phone');
            $isDirty = true;
        }

        if ($request->has('address') && $user->address !== $request->input('address')) {
            $user->address = $request->input('address');
            $isDirty = true;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
            $isDirty = true;
        }

        if ($isDirty) {
            $user->save();
        }

        return response()->json([
            'message' => 'User profile updated successfully',
            'data' => $user,
        ]);
    }
}
