<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventScanner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Role; // Added this import for the new test method

class EventScannerController extends Controller
{
    /**
     * Get all scanner assignments for an event
     */
    public function getEventScanners(Event $event): JsonResponse
    {
        $scanners = $event->scanners()
                          ->with(['user:id,name,email,role_id', 'user.role:id,name'])
                          ->orderBy('role', 'desc')
                          ->orderBy('assigned_at', 'asc')
                          ->get();

        return response()->json([
            'data' => $scanners,
            'summary' => [
                'total' => $scanners->count(),
                'primary' => $scanners->where('role', 'primary')->count(),
                'secondary' => $scanners->where('role', 'secondary')->count(),
                'active' => $scanners->where('is_active', true)->count()
            ]
        ]);
    }

    /**
     * Assign a scanner to an event
     */
    public function assignScanner(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:primary,secondary',
        ]);

        // Check if user is already assigned to this event
        $existingAssignment = EventScanner::where('event_id', $event->id)
                                        ->where('user_id', $validated['user_id'])
                                        ->first();

        if ($existingAssignment) {
            if ($existingAssignment->is_active) {
                return response()->json([
                    'message' => 'User is already assigned as a scanner for this event',
                    'errors' => ['user_id' => ['User is already assigned to this event']]
                ], 422);
            } else {
                // Reactivate existing assignment
                $existingAssignment->reactivate();
                $existingAssignment->update(['role' => $validated['role']]);
                
                return response()->json([
                    'message' => 'Scanner assignment reactivated successfully',
                    'data' => $existingAssignment->load(['user:id,name,email,role_id', 'user.role:id,name'])
                ], 200);
            }
        }

        // Check if this is a primary scanner assignment
        if ($validated['role'] === 'primary') {
            // Deactivate any existing primary scanner
            EventScanner::where('event_id', $event->id)
                       ->where('role', 'primary')
                       ->where('is_active', true)
                       ->update(['is_active' => false, 'deactivated_at' => now()]);
        }

        // Create new assignment
        $scannerAssignment = EventScanner::create([
            'event_id' => $event->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
            'is_active' => true,
            'assigned_at' => now()
        ]);

        return response()->json([
            'message' => 'Scanner assigned successfully',
            'data' => $scannerAssignment->load(['user:id,name,email,role_id', 'user.role:id,name'])
        ], 201);
    }

    /**
     * Update scanner role
     */
    public function updateScannerRole(Request $request, Event $event, EventScanner $scanner): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:primary,secondary',
        ]);

        // If changing to primary, deactivate existing primary scanner
        if ($validated['role'] === 'primary') {
            EventScanner::where('event_id', $event->id)
                       ->where('role', 'primary')
                       ->where('is_active', true)
                       ->where('id', '!=', $scanner->id)
                       ->update(['is_active' => false, 'deactivated_at' => now()]);
        }

        $scanner->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'Scanner role updated successfully',
            'data' => $scanner->load(['user:id,name,email,role_id', 'user.role:id,name'])
        ]);
    }

    /**
     * Deactivate scanner assignment
     */
    public function deactivateScanner(Event $event, EventScanner $scanner): JsonResponse
    {
        $scanner->deactivate();

        return response()->json([
            'message' => 'Scanner assignment deactivated successfully'
        ]);
    }

    /**
     * Reactivate scanner assignment
     */
    public function reactivateScanner(Event $event, EventScanner $scanner): JsonResponse
    {
        // If reactivating as primary, deactivate existing primary scanner
        if ($scanner->role === 'primary') {
            EventScanner::where('event_id', $event->id)
                       ->where('role', 'primary')
                       ->where('is_active', true)
                       ->update(['is_active' => false, 'deactivated_at' => now()]);
        }

        $scanner->reactivate();

        return response()->json([
            'message' => 'Scanner assignment reactivated successfully',
            'data' => $scanner->load(['user:id,name,email,role_id', 'user.role:id,name'])
        ]);
    }

    /**
     * Get available scanner users (users with scanner role)
     */
    public function getAvailableScanners(): JsonResponse
    {
        $scanners = User::whereHas('role', function ($query) {
                        $query->whereRaw('LOWER(name) = ?', ['scanner']);
                    })
                    ->with('role:id,name')
                    ->select('id', 'name', 'email', 'role_id')
                    ->orderBy('name')
                    ->get();

        return response()->json(['data' => $scanners]);
    }

    /**
     * Bulk assign scanners to an event
     */
    public function bulkAssignScanners(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'scanners' => 'required|array|min:1',
            'scanners.*.user_id' => 'required|exists:users,id',
            'scanners.*.role' => 'required|in:primary,secondary',
        ]);

        DB::beginTransaction();
        try {
            $assignedScanners = [];
            $primaryScannerFound = false;

            foreach ($validated['scanners'] as $scannerData) {
                // Check if user is already assigned
                $existingAssignment = EventScanner::where('event_id', $event->id)
                                                ->where('user_id', $scannerData['user_id'])
                                                ->first();

                if ($existingAssignment) {
                    if ($existingAssignment->is_active) {
                        continue; // Skip if already active
                    }
                    // Reactivate and update role
                    $existingAssignment->reactivate();
                    $existingAssignment->update(['role' => $scannerData['role']]);
                    $assignedScanners[] = $existingAssignment;
                } else {
                    // Create new assignment
                    $scannerAssignment = EventScanner::create([
                        'event_id' => $event->id,
                        'user_id' => $scannerData['user_id'],
                        'role' => $scannerData['role'],
                        'is_active' => true,
                        'assigned_at' => now()
                    ]);
                    $assignedScanners[] = $scannerAssignment;
                }

                if ($scannerData['role'] === 'primary') {
                    $primaryScannerFound = true;
                }
            }

            // If no primary scanner was assigned, make the first one primary
            if (!$primaryScannerFound && !empty($assignedScanners)) {
                $assignedScanners[0]->update(['role' => 'primary']);
            }

            // Deactivate any existing primary scanners not in the new list
            EventScanner::where('event_id', $event->id)
                       ->where('role', 'primary')
                       ->where('is_active', true)
                       ->whereNotIn('user_id', collect($validated['scanners'])->pluck('user_id'))
                       ->update(['is_active' => false, 'deactivated_at' => now()]);

            DB::commit();

            return response()->json([
                'message' => 'Scanners assigned successfully',
                'data' => collect($assignedScanners)->load(['user:id,name,email,role_id', 'user.role:id,name'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to assign scanners',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
