<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Services\EventStatusService;

class ScanController extends Controller
{
    public function getEventScans(Event $event): JsonResponse
    {
        $scans = Scan::with(['guest.cardClass', 'scannedBy'])
            ->whereHas('guest', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $scans]);
    }

    public function createScan(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'scanned_by' => 'required|exists:users,id',
        ]);

        // Check if the guest belongs to this event
        $guest = Guest::where('id', $validated['guest_id'])
            ->where('event_id', $event->id)
            ->with(['cardClass'])
            ->first();

        if (!$guest) {
            return response()->json([
                'message' => 'Guest not found in this event'
            ], 404);
        }

        // Get the quantity from card class max_guests
        $quantity = $guest->cardClass->max_guests;

        // Check if scan already exists for this guest
        $existingScan = Scan::where('guest_id', $validated['guest_id'])->first();

        if ($existingScan) {
            // Check if scan count has reached the maximum
            if ($existingScan->scan_count >= $quantity) {
                return response()->json([
                    'message' => 'Guest has already been scanned the maximum number of times allowed',
                    'scan' => $existingScan->load(['guest', 'scannedBy'])
                ], 422);
            }

            // Update existing scan
            $existingScan->update([
                'scan_count' => $existingScan->scan_count + 1,
                'scanned_by' => $validated['scanned_by'],
                'scanned_date' => Carbon::now(),
                'status' => ($existingScan->scan_count + 1) >= $quantity ? 'scanned' : 'not_scanned'
            ]);

            $scan = $existingScan->load(['guest.cardClass', 'scannedBy']);
        } else {
            // Create new scan
            $scan = Scan::create([
                'guest_id' => $validated['guest_id'],
                'quantity' => $quantity,
                'scan_count' => 1,
                'scanned_by' => $validated['scanned_by'],
                'scanned_date' => Carbon::now(),
                'status' => $quantity <= 1 ? 'scanned' : 'not_scanned'
            ]);

            $scan->load(['guest.cardClass', 'scannedBy']);
        }
        // Update event status
        EventStatusService::updateEventStatus($event);
        return response()->json([
            'message' => 'Scan recorded successfully',
            'scan' => $scan
        ], 201);
    }

    public function getGuestByQrCode(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => 'required|string'
        ]);

        // Find guest by QR code (invite_code)
        $guest = Guest::where('event_id', $event->id)
            ->where('invite_code', $validated['qr_code'])
            ->with(['cardClass'])
            ->first();

        if (!$guest) {
            return response()->json([
                'message' => 'Guest not found'
            ], 404);
        }

        return response()->json([
            'guest' => $guest
        ]);
    }
}
