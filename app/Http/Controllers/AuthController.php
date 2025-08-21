<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.'],
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password you entered is incorrect.'],
            ]);
        }

        // Check if user is active
        if ($user->status === 'inactive') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact an administrator.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role_id' => $user->role_id,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role_id' => $user->role_id,
        ]);
    }

    /**
     * Get scanner users
     */
    public function getScannerUsers(): JsonResponse
    {
        $scanners = User::whereHas('role', function ($query) {
                        $query->where('name', 'scanner');
                    })
                    ->with('role:id,name')
                    ->select('id', 'name', 'email', 'role_id')
                    ->orderBy('name')
                    ->get();

        return response()->json(['data' => $scanners]);
    }

    /**
     * Get events assigned to the authenticated scanner user
     */
    public function getMyScannerEvents(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user || !$user->isScanner()) {
            return response()->json([
                'message' => 'Unauthorized. User must be a scanner.'
            ], 403);
        }

        $events = $user->scannerEvents()
                      ->with(['customer:id,name', 'eventType:id,name', 'cardType:id,name'])
                      ->orderBy('event_date', 'desc')
                      ->get();

        return response()->json(['data' => $events]);
    }
} 