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
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
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
        $users = User::select('id', 'name', 'first_name', 'last_name', 'email', 'status')
                    ->where('status', 'active')
                    ->orderBy('name')
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
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        // Ensure name field is populated
        if (isset($validated['first_name']) && isset($validated['last_name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        } elseif (!isset($validated['name'])) {
            $validated['name'] = 'User'; // Default name if neither is provided
        }

        $user = User::create([
            'name' => $validated['name'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
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
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_number' => 'required|string|max:255',
            'password' => 'sometimes|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        // Ensure name field is populated
        if (isset($validated['first_name']) && isset($validated['last_name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        } elseif (isset($validated['name'])) {
            // If only name is provided, clear first_name and last_name
            $validated['first_name'] = null;
            $validated['last_name'] = null;
        }

        $updateData = [
            'name' => $validated['name'] ?? $user->name,
            'first_name' => $validated['first_name'] ?? $user->first_name,
            'last_name' => $validated['last_name'] ?? $user->last_name,
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
        if ($user->hasRole('Admin')) {
            $adminRoleId = Role::where('name', 'Admin')->value('id');
            if ($adminRoleId) {
                $adminCount = User::where('role_id', $adminRoleId)->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'message' => 'Cannot delete the last admin user'
                    ], 422);
                }
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
        $adminRoleId = Role::where('name', 'Admin')->value('id');
        $scannerRoleId = Role::where('name', 'Scanner')->value('id');

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'admin_users' => $adminRoleId ? User::where('role_id', $adminRoleId)->count() : 0,
            'scanner_users' => $scannerRoleId ? User::where('role_id', $scannerRoleId)->count() : 0,
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
