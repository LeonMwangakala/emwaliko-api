<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Role filter
        if ($request->has('role_id') && $request->role_id !== '') {
            $query->where('role_id', $request->role_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get all users (for dropdowns, etc.)
     */
    public function getAll(): JsonResponse
    {
        $users = User::select('id', 'first_name', 'last_name', 'email', 'status')
                    ->where('status', 'active')
                    ->orderBy('first_name')
                    ->get();

        return response()->json($users);
    }

    /**
     * Get single user
     */
    public function show(User $user): JsonResponse
    {
        $user->load('role');
        return response()->json($user);
    }

    /**
     * Create new user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'status' => $validated['status'] ?? 'active',
        ]);

        $user->load('role');

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_number' => 'required|string|max:255',
            'password' => 'sometimes|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'role_id' => $validated['role_id'],
        ];

        // Only update password if provided
        if (isset($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Only update status if provided
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        $user->update($updateData);
        $user->load('role');

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'message' => "User {$newStatus} successfully",
            'user' => $user->load('role')
        ]);
    }

    /**
     * Activate user
     */
    public function activate(User $user): JsonResponse
    {
        $user->update(['status' => 'active']);

        return response()->json([
            'message' => 'User activated successfully',
            'user' => $user->load('role')
        ]);
    }

    /**
     * Deactivate user
     */
    public function deactivate(User $user): JsonResponse
    {
        $user->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'User deactivated successfully',
            'user' => $user->load('role')
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting the last admin
        if ($user->role_id === 1) {
            $adminCount = User::where('role_id', 1)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot delete the last admin user'
                ], 422);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'admin_users' => User::where('role_id', 1)->count(),
            'scanner_users' => User::where('role_id', 2)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all roles
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::all();
        return response()->json($roles);
    }
}
