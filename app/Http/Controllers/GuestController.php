<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Event;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\EventStatusService;
use App\Services\SalesService;

class GuestController extends Controller
{
    public function index(): JsonResponse
    {
        $guests = Guest::with(['event', 'cardClass'])->paginate(10);
        return response()->json($guests);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:20',
            'card_class_id' => 'required|exists:card_classes,id',
            'rsvp_status' => 'sometimes|in:Yes,No,Maybe,Pending'
        ]);

        // Validate and normalize phone number
        $phoneValidation = PhoneNumberService::validateAndNormalize($validated['phone_number']);
        if (!$phoneValidation['is_valid']) {
            return response()->json([
                'message' => 'Invalid phone number format',
                'errors' => ['phone_number' => [$phoneValidation['error']]]
            ], 422);
        }

        // Use normalized phone number
        $validated['phone_number'] = $phoneValidation['normalized'];

        // Check for phone number uniqueness within the event
        $existingGuest = Guest::where('event_id', $validated['event_id'])
            ->where('phone_number', $validated['phone_number'])
            ->first();

        if ($existingGuest) {
            return response()->json([
                'message' => 'A guest with this phone number already exists in this event',
                'errors' => ['phone_number' => ['This phone number is already registered for this event.']]
            ], 422);
        }

        $guest = Guest::create($validated);
        // Update event status and sales
        $event = Event::find($validated['event_id']);
        if ($event) {
            EventStatusService::updateEventStatus($event);
            SalesService::updateSalesForGuestCountChange($event);
        }
        return response()->json($guest->load(['event', 'cardClass']), 201);
    }

    public function show(Guest $guest): JsonResponse
    {
        $guest->load(['event.customer', 'event.eventType', 'cardClass']);
        return response()->json($guest);
    }

    public function update(Request $request, Guest $guest): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'phone_number' => 'sometimes|required|string|max:20',
            'card_class_id' => 'sometimes|required|exists:card_classes,id',
            'rsvp_status' => 'sometimes|in:Yes,No,Maybe,Pending'
        ]);

        // Validate and normalize phone number if provided
        if (isset($validated['phone_number'])) {
            $phoneValidation = PhoneNumberService::validateAndNormalize($validated['phone_number']);
            if (!$phoneValidation['is_valid']) {
                return response()->json([
                    'message' => 'Invalid phone number format',
                    'errors' => ['phone_number' => [$phoneValidation['error']]]
                ], 422);
            }

            // Use normalized phone number
            $validated['phone_number'] = $phoneValidation['normalized'];

            // Check for phone number uniqueness within the event (excluding current guest)
            $existingGuest = Guest::where('event_id', $guest->event_id)
                ->where('phone_number', $validated['phone_number'])
                ->where('id', '!=', $guest->id)
                ->first();

            if ($existingGuest) {
                return response()->json([
                    'message' => 'A guest with this phone number already exists in this event',
                    'errors' => ['phone_number' => ['This phone number is already registered for this event.']]
                ], 422);
            }
        }

        $guest->update($validated);

        return response()->json($guest->load(['event', 'cardClass']));
    }

    public function destroy(Guest $guest): JsonResponse
    {
        $event = $guest->event;
        $guest->delete();
        // Update event status and sales
        if ($event) {
            EventStatusService::updateEventStatus($event);
            SalesService::updateSalesForGuestCountChange($event);
        }
        return response()->json(['message' => 'Guest deleted successfully']);
    }

    public function rsvp(Request $request, string $inviteCode): JsonResponse
    {
        $guest = Guest::where('invite_code', $inviteCode)->first();

        if (!$guest) {
            return response()->json(['message' => 'Invalid invite code'], 404);
        }

        $validated = $request->validate([
            'rsvp_status' => 'required|in:Yes,No,Maybe'
        ]);

        $guest->update(['rsvp_status' => $validated['rsvp_status']]);

        return response()->json([
            'message' => 'RSVP updated successfully',
            'guest' => $guest->load(['event.customer', 'event.eventType', 'cardClass'])
        ]);
    }

    public function getGuestByInviteCode(string $inviteCode): JsonResponse
    {
        $guest = Guest::where('invite_code', $inviteCode)
            ->with(['event.customer', 'event.eventType', 'cardClass'])
            ->first();

        if (!$guest) {
            return response()->json(['message' => 'Invalid invite code'], 404);
        }

        return response()->json($guest);
    }

    public function getEventGuests(Event $event, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        
        $query = $event->guests()->with(['cardClass']);
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }
        
        $guests = $query->paginate($perPage);
        
        // Add QR code base64 data to each guest
        $guests->getCollection()->transform(function ($guest) {
            $guest->qr_code_base64 = $guest->qr_code_base64;
            return $guest;
        });
        
        return response()->json($guests);
    }

    public function getAllEventGuests(Event $event): JsonResponse
    {
        $guests = $event->guests()->with(['cardClass'])->get();
        
        // Add QR code base64 data to each guest
        $guests->transform(function ($guest) {
            $guest->qr_code_base64 = $guest->qr_code_base64;
            return $guest;
        });
        
        return response()->json($guests);
    }

    public function bulkCreate(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'guests' => 'required|array|min:1',
            'guests.*.name' => 'required|string|max:255',
            'guests.*.title' => 'nullable|string|max:255',
            'guests.*.phone_number' => 'required|string|max:20',
            'guests.*.card_class_id' => 'required|exists:card_classes,id'
        ]);

        // Get existing phone numbers for this event
        $existingPhoneNumbers = $event->guests()->pluck('phone_number')->toArray();
        
        // Check for duplicates within the uploaded data
        $uploadedPhoneNumbers = array_column($validated['guests'], 'phone_number');
        $duplicatesInUpload = array_diff_assoc($uploadedPhoneNumbers, array_unique($uploadedPhoneNumbers));
        
        // Check for duplicates with existing guests
        $duplicatesWithExisting = array_intersect($uploadedPhoneNumbers, $existingPhoneNumbers);
        
        $errors = [];
        $validGuests = [];
        $invalidGuests = [];

        foreach ($validated['guests'] as $index => $guestData) {
            $guestErrors = [];
            
            // Validate and normalize phone number
            $phoneValidation = PhoneNumberService::validateAndNormalize($guestData['phone_number']);
            if (!$phoneValidation['is_valid']) {
                $guestErrors[] = $phoneValidation['error'];
            } else {
                // Use normalized phone number
                $guestData['phone_number'] = $phoneValidation['normalized'];
            }
            
            // Check if phone number is duplicate within upload
            if (in_array($guestData['phone_number'], $duplicatesInUpload)) {
                $guestErrors[] = 'Duplicate phone number in uploaded data';
            }
            
            // Check if phone number already exists in event
            if (in_array($guestData['phone_number'], $existingPhoneNumbers)) {
                $guestErrors[] = 'Phone number already exists in this event';
            }
            
            if (empty($guestErrors)) {
                $validGuests[] = $guestData;
            } else {
                $invalidGuests[] = [
                    'row' => $index + 1,
                    'data' => $guestData,
                    'errors' => $guestErrors
                ];
            }
        }

        // Create valid guests
        $createdGuests = [];
        foreach ($validGuests as $guestData) {
            $guestData['event_id'] = $event->id;
            $createdGuests[] = Guest::create($guestData);
        }

        $response = [
            'message' => count($createdGuests) . ' guests created successfully',
            'guests' => $createdGuests,
            'summary' => [
                'total_uploaded' => count($validated['guests']),
                'created' => count($createdGuests),
                'failed' => count($invalidGuests)
            ]
        ];

        if (!empty($invalidGuests)) {
            $response['invalid_guests'] = $invalidGuests;
        }

        return response()->json($response, 201);
    }

    public function generateMissingQrCodes(Event $event): JsonResponse
    {
        try {
            $guestsWithoutQrCodes = $event->guests()
                ->whereNull('qr_code_path')
                ->orWhere('qr_code_path', '')
                ->get();
            
            $count = 0;
            foreach ($guestsWithoutQrCodes as $guest) {
                try {
                    $guest->generateQrCode();
                    $count++;
                } catch (\Exception $e) {
                    \Log::error("Failed to generate QR code for guest {$guest->id}: " . $e->getMessage());
                }
            }
            
            return response()->json([
                'message' => "Generated QR codes for {$count} guests",
                'generated_count' => $count,
                'total_guests_without_qr' => $guestsWithoutQrCodes->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate QR codes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function regenerateQrCode(Guest $guest): JsonResponse
    {
        try {
            $guest->regenerateQrCode();
            
            return response()->json([
                'message' => 'QR code regenerated successfully',
                'guest' => $guest->load(['event', 'cardClass'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to regenerate QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function regenerateAllQrCodes(Event $event): JsonResponse
    {
        try {
            $allGuests = $event->guests()->get();
            $totalGuests = $allGuests->count();
            $successCount = 0;
            $failedCount = 0;
            
            foreach ($allGuests as $guest) {
                try {
                    $guest->regenerateQrCode();
                    $successCount++;
                } catch (\Exception $e) {
                    \Log::error("Failed to regenerate QR code for guest {$guest->id}: " . $e->getMessage());
                    $failedCount++;
                }
            }
            
            return response()->json([
                'message' => "Regenerated QR codes for {$successCount} out of {$totalGuests} guests",
                'total_guests' => $totalGuests,
                'success_count' => $successCount,
                'failed_count' => $failedCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to regenerate QR codes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 