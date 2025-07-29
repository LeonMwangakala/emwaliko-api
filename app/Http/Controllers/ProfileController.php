<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'bio' => $user->bio,
            'user_code' => $user->user_code,
            'country' => $user->country,
            'region' => $user->region,
            'postal_code' => $user->postal_code,
            'profile_picture' => $user->profile_picture,
            'role_id' => $user->role_id,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'required|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'country' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
        ]);

        // Update the name field to combine first_name and last_name
        $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'bio' => $user->bio,
                'user_code' => $user->user_code,
                'country' => $user->country,
                'region' => $user->region,
                'postal_code' => $user->postal_code,
                'profile_picture' => $user->profile_picture,
                'role_id' => $user->role_id,
            ]
        ]);
    }

    public function updateProfilePicture(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            // Delete old profile picture if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new profile picture
            $file = $request->file('profile_picture');
            $fileName = 'profile_pictures/' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            $path = $file->storeAs('profile_pictures', $fileName, 'public');
            
            // Update user profile picture path
            $user->update(['profile_picture' => $path]);

            return response()->json([
                'message' => 'Profile picture updated successfully',
                'profile_picture' => Storage::url($path),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'bio' => $user->bio,
                    'user_code' => $user->user_code,
                    'country' => $user->country,
                    'region' => $user->region,
                    'postal_code' => $user->postal_code,
                    'profile_picture' => $user->profile_picture,
                    'role_id' => $user->role_id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload profile picture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 