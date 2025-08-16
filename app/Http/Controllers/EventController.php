<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\CardType;
use App\Models\CardClass;
use App\Models\Package;
use App\Services\EventStatusService;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with([
            'customer',
            'eventType',
            'cardType',
            'package',
            'country',
            'region',
            'district'
        ])->withCount('guests');

        // Filter by status if provided
        if ($request->has('status') && $request->get('status') !== 'all') {
            $status = $request->get('status');
            $query->where('status', $status);
        }

        // Search functionality
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                  ->orWhere('event_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('eventType', function ($eventTypeQuery) use ($search) {
                      $eventTypeQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('package', function ($packageQuery) use ($search) {
                      $packageQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 10);
        
        $events = $query->paginate($perPage);

        // Convert event dates to ISO string without timezone conversion
        $events->getCollection()->transform(function ($event) {
            if ($event->event_date) {
                $event->event_date = Carbon::parse($event->event_date)->toISOString();
            }
            if ($event->notification_date) {
                $event->notification_date = Carbon::parse($event->notification_date)->toISOString();
            }
            // Add card design base64 data
            $event->card_design_base64 = $event->card_design_base64;
            // Add guests count
            $event->guests_count = $event->guests()->count();
            return $event;
        });

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'event_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'event_location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_url' => 'nullable|string',
            'event_type_id' => 'nullable|exists:event_types,id',
            'customer_id' => 'nullable|exists:customers,id',
            'card_type_id' => 'nullable|exists:card_types,id',
            'package_id' => 'nullable|exists:packages,id',
            'notification_date' => 'nullable|date',
            'notification_time' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'country_id' => 'nullable|exists:countries,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'status' => 'nullable|in:initiated,inprogress,notified,scanned,completed,cancelled',
        ]);

        // Merge date and time fields into datetime
        $eventDateTime = null;
        if ($validated['event_date'] && $validated['event_time']) {
            // Create datetime using user's input time without timezone conversion
            $eventDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $validated['event_date'] . ' ' . $validated['event_time'] . ':00'
            );
        }

        $notificationDateTime = null;
        if ($validated['notification_date'] && $validated['notification_time']) {
            // Create datetime using user's input time without timezone conversion
            $notificationDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $validated['notification_date'] . ' ' . $validated['notification_time'] . ':00'
            );
        }

        // Prepare data for database
        $eventData = [
            'event_name' => $validated['event_name'],
            'event_location' => $validated['event_location'] ?? '',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'google_maps_url' => $validated['google_maps_url'] && $validated['google_maps_url'] !== '' ? $validated['google_maps_url'] : null,
            'event_date' => $eventDateTime,
            'notification_date' => $notificationDateTime,
            'customer_id' => $validated['customer_id'] ?? 1, // Default to first customer if not provided
            'event_type_id' => $validated['event_type_id'] ?? 1, // Default to first event type if not provided
            'card_type_id' => $validated['card_type_id'] ?? 1, // Default to first card type if not provided
            'package_id' => $validated['package_id'] ?? 1, // Default to first package
            'country_id' => $validated['country_id'] ?? null,
            'region_id' => $validated['region_id'] ?? null,
            'district_id' => $validated['district_id'] ?? null,
            'status' => $validated['status'] ?? 'initiated',
        ];

        $event = Event::create($eventData);

        // Create initial sales record
        SalesService::updateEventSales($event);

        return response()->json($event->load([
            'customer',
            'eventType',
            'cardType',
            'package',
            'country',
            'region',
            'district'
        ]), 201);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load([
            'customer',
            'eventType',
            'cardType',
            'package',
            'country',
            'region',
            'district',
            'guests.cardClass'
        ]);

        // Convert event dates to ISO string without timezone conversion
        if ($event->event_date && !is_null($event->event_date)) {
            $event->event_date = Carbon::parse($event->event_date)->toISOString();
        }
        if ($event->notification_date && !is_null($event->notification_date)) {
            $event->notification_date = Carbon::parse($event->notification_date)->toISOString();
        }

        // Add card design base64 data
        $event->card_design_base64 = $event->card_design_base64;

        // Add guests count
        $event->guests_count = $event->guests()->count();

        return response()->json($event);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        // Check if event can be updated
        if (!EventStatusService::canUpdateEvent($event)) {
            return response()->json([
                'message' => 'Cannot update event that is completed or cancelled',
                'error' => 'EVENT_UPDATE_RESTRICTED'
            ], 422);
        }

        $validated = $request->validate([
            'event_name' => 'sometimes|required|string|max:255',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'event_type_id' => 'sometimes|required|exists:event_types,id',
            'card_type_id' => 'sometimes|required|exists:card_types,id',
            'package_id' => 'sometimes|required|exists:packages,id',
            'event_location' => 'sometimes|required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_url' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'event_time' => ['sometimes', 'required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'notification_date' => 'nullable|date',
            'notification_time' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'scanner_person' => 'nullable|string|max:255',
            'card_design_path' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id'
        ]);

        // Check if package is being changed
        $packageChanged = isset($validated['package_id']) && $validated['package_id'] !== $event->package_id;

        // Handle event date and time combination
        if (isset($validated['event_date']) && isset($validated['event_time'])) {
            $eventDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $validated['event_date'] . ' ' . $validated['event_time'] . ':00'
            );
            $validated['event_date'] = $eventDateTime;
        }

        // Handle notification date and time combination
        if (isset($validated['notification_date']) && isset($validated['notification_time'])) {
            $notificationDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $validated['notification_date'] . ' ' . $validated['notification_time'] . ':00'
            );
            $validated['notification_date'] = $notificationDateTime;
        }

        // Remove time fields from update data
        $updateData = array_filter($validated, function($key) {
            return !in_array($key, ['event_time', 'notification_time']);
        }, ARRAY_FILTER_USE_KEY);

        // Handle empty strings for optional fields
        if (isset($updateData['google_maps_url']) && $updateData['google_maps_url'] === '') {
            $updateData['google_maps_url'] = null;
        }
        if (isset($updateData['latitude']) && $updateData['latitude'] === '') {
            $updateData['latitude'] = null;
        }
        if (isset($updateData['longitude']) && $updateData['longitude'] === '') {
            $updateData['longitude'] = null;
        }

        $event->update($updateData);

        // Update sales if package changed
        if ($packageChanged) {
            SalesService::updateSalesForPackageChange($event, $validated['package_id']);
        }

        return response()->json($event->load([
            'customer',
            'eventType',
            'cardType',
            'package',
            'country',
            'region',
            'district'
        ]));
    }

    public function uploadCardDesign(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'card_design_base64' => 'required|string',
            'file_name' => 'required|string|max:255',
            'file_type' => 'required|string|in:image/jpeg,image/png,image/jpg,image/gif'
        ]);

        try {
            // Extract base64 data
            $base64Data = $validated['card_design_base64'];
            
            // Remove data URL prefix if present
            if (strpos($base64Data, 'data:') === 0) {
                $base64Data = explode(',', $base64Data)[1];
            }
            
            // Decode base64 to binary data
            $imageData = base64_decode($base64Data);
            
            if ($imageData === false) {
                return response()->json([
                    'message' => 'Invalid base64 image data',
                    'error' => 'Could not decode base64 data'
                ], 422);
            }

            // Create temporary file to validate dimensions
            $tempFile = tempnam(sys_get_temp_dir(), 'card_design_');
            file_put_contents($tempFile, $imageData);
            
            // Validate image dimensions
            $imageInfo = getimagesize($tempFile);
            
            if (!$imageInfo) {
                unlink($tempFile);
                return response()->json([
                    'message' => 'Invalid image file',
                    'error' => 'Could not read image dimensions'
                ], 422);
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // Define allowed dimensions (maintaining 3000x4200 aspect ratio)
            $allowedDimensions = [
                [3000, 4200], // Original size
                [1500, 2100], // Half size
                [1000, 1400], // 1/3 size
                [750, 1050],  // 1/4 size
                [600, 840],   // 1/5 size
            ];
            
            $isValidDimension = false;
            foreach ($allowedDimensions as [$allowedWidth, $allowedHeight]) {
                if ($width === $allowedWidth && $height === $allowedHeight) {
                    $isValidDimension = true;
                    break;
                }
            }
            
            if (!$isValidDimension) {
                unlink($tempFile);
                $dimensionList = implode(', ', array_map(function($dim) {
                    return "{$dim[0]}x{$dim[1]}";
                }, $allowedDimensions));
                
                return response()->json([
                    'message' => 'Invalid image dimensions',
                    'error' => "Image must be exactly one of these dimensions: {$dimensionList}",
                    'current_dimensions' => "{$width}x{$height}",
                    'allowed_dimensions' => $allowedDimensions
                ], 422);
            }

            // Delete old card design if exists
            if ($event->card_design_path) {
                $oldPath = storage_path('app/public/' . $event->card_design_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Generate unique filename
            $extension = pathinfo($validated['file_name'], PATHINFO_EXTENSION);
            $filename = 'card-designs/' . time() . '_' . uniqid() . '.' . $extension;
            $fullPath = storage_path('app/public/' . $filename);
            
            // Ensure directory exists
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Store the image file
            file_put_contents($fullPath, $imageData);
            
            // Clean up temp file
            unlink($tempFile);
            
            // Update event with new card design path
            $event->update(['card_design_path' => $filename]);

            return response()->json([
                'message' => 'Card design uploaded successfully',
                'card_design_path' => $filename,
                'dimensions' => "{$width}x{$height}"
            ]);

        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return response()->json([
                'message' => 'Failed to upload card design',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCardDesign(Event $event): JsonResponse
    {
        try {
            if (!$event->card_design_path) {
                return response()->json([
                    'message' => 'No card design found',
                    'card_design_base64' => null
                ], 404);
            }

            $filePath = storage_path('app/public/' . $event->card_design_path);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'message' => 'Card design file not found',
                    'card_design_base64' => null
                ], 404);
            }

            // Read file and convert to base64
            $imageData = file_get_contents($filePath);
            $base64Data = base64_encode($imageData);
            
            // Determine mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            // Create data URL
            $dataUrl = "data:{$mimeType};base64,{$base64Data}";

            return response()->json([
                'message' => 'Card design retrieved successfully',
                'card_design_base64' => $dataUrl,
                'file_name' => basename($event->card_design_path),
                'file_type' => $mimeType
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve card design',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCardDesign(Event $event): JsonResponse
    {
        try {
            if ($event->card_design_path) {
                $path = storage_path('app/public/' . $event->card_design_path);
                if (file_exists($path)) {
                    unlink($path);
                }
                
                $event->update(['card_design_path' => null]);
                
                return response()->json([
                    'message' => 'Card design deleted successfully'
                ]);
            }

            return response()->json([
                'message' => 'No card design to delete'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete card design',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:initiated,inprogress,notified,scanned,completed,cancelled',
        ]);

        $event->update(['status' => $validated['status']]);

        // Load relationships and add guest count
        $event->load([
            'customer',
            'eventType',
            'cardType',
            'package',
            'country',
            'region',
            'district'
        ]);
        
        // Add guests count
        $event->guests_count = $event->guests()->count();

        return response()->json([
            'message' => 'Event status updated successfully',
            'event' => $event
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function getByEventCode(string $eventCode): JsonResponse
    {
        $event = Event::where('event_code', $eventCode)
            ->with([
                'customer',
                'eventType',
                'cardType',
                'package',
                'guests.cardClass'
            ])
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // Convert event dates to ISO string without timezone conversion
        if ($event->event_date && !is_null($event->event_date)) {
            $event->event_date = Carbon::parse($event->event_date)->toISOString();
        }
        if ($event->notification_date && !is_null($event->notification_date)) {
            $event->notification_date = Carbon::parse($event->notification_date)->toISOString();
        }

        // Add guests count
        $event->guests_count = $event->guests()->count();

        return response()->json($event);
    }

    public function getEventOptions(): JsonResponse
    {
        return response()->json([
            'customers' => Customer::all(),
            'event_types' => EventType::all(),
            'card_classes' => CardClass::all(),
            'packages' => Package::all()
        ]);
    }
} 