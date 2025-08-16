<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\EventStatusService;
use App\Services\NextSmsService;
use App\Services\WhatsAppService;
use App\Services\GuestCardService;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Notification::with(['guest'])->paginate(15);
        return response()->json($notifications);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'message' => 'required|string',
            'notification_type' => 'required|in:SMS,WhatsApp,WHATSAPP',
            'status' => 'in:Sent,Not Sent',
        ]);

        // Normalize notification type
        if (strtoupper($validated['notification_type']) === 'WHATSAPP') {
            $validated['notification_type'] = 'WhatsApp';
        }

        $notification = Notification::create($validated);

        return response()->json($notification->load('guest'), 201);
    }

    public function show(Notification $notification): JsonResponse
    {
        $notification->load(['guest.event']);
        return response()->json($notification);
    }

    public function update(Request $request, Notification $notification): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'sometimes|required|string',
            'notification_type' => 'sometimes|required|in:SMS,WhatsApp,WHATSAPP',
            'status' => 'sometimes|in:Sent,Not Sent',
        ]);

        // Normalize notification type if present
        if (isset($validated['notification_type']) && strtoupper($validated['notification_type']) === 'WHATSAPP') {
            $validated['notification_type'] = 'WhatsApp';
        }

        $notification->update($validated);

        return response()->json($notification->load('guest'));
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();
        return response()->json(['message' => 'Notification deleted successfully']);
    }

    public function destroyEventNotification(Event $event, $notificationId): JsonResponse
    {
        $notification = Notification::whereHas('guest', function ($query) use ($event) {
            $query->where('event_id', $event->id);
        })->findOrFail($notificationId);
        
        // Check if notification has been sent
        if ($notification->status === 'Sent') {
            return response()->json([
                'message' => 'Cannot delete notification that has been sent',
                'error' => 'SENT_NOTIFICATION_DELETE_RESTRICTED'
            ], 422);
        }
        
        $notification->delete();
        return response()->json(['message' => 'Notification deleted successfully']);
    }

    public function getEventNotifications(Request $request, Event $event): JsonResponse
    {
        // Get pagination parameters
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        
        $query = Notification::whereHas('guest', function ($query) use ($event) {
            $query->where('event_id', $event->id);
        })->with(['guest:id,name,phone_number']);
        
        // Apply search filter if search term is provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', function ($guestQuery) use ($search) {
                    $guestQuery->where('name', 'like', '%' . $search . '%')
                               ->orWhere('phone_number', 'like', '%' . $search . '%');
                })
                ->orWhere('message', 'like', '%' . $search . '%')
                ->orWhere('notification_type', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%');
            });
        }
        
        $notifications = $query->orderBy('created_at', 'desc')
                               ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json($notifications);
    }

    public function getAvailableGuestsForNotificationType(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'notification_type' => 'required|in:SMS,WhatsApp,WHATSAPP'
        ]);

        // Normalize notification type to handle both WhatsApp and WHATSAPP
        $notificationType = $validated['notification_type'];
        if (strtoupper($notificationType) === 'WHATSAPP') {
            $notificationType = 'WhatsApp';
        }

        // Get all guests for this event
        $allGuests = $event->guests()->get();

        // Get guest IDs that already have this notification type
        $existingGuestIds = Notification::whereHas('guest', function ($query) use ($event) {
            $query->where('event_id', $event->id);
        })
        ->where('notification_type', $notificationType)
        ->pluck('guest_id')
        ->toArray();

        // Filter out guests who already have this notification type
        $availableGuests = $allGuests->filter(function ($guest) use ($existingGuestIds, $notificationType) {
            // For WhatsApp, only include guests who have cards generated
            if ($notificationType === 'WhatsApp' && !$guest->guest_card_path) {
                return false;
            }
            
            return !in_array($guest->id, $existingGuestIds);
        })->values();

        // Count guests filtered out due to missing cards (for WhatsApp only)
        $guestsWithoutCards = 0;
        if ($notificationType === 'WhatsApp') {
            $guestsWithoutCards = $allGuests->filter(function ($guest) {
                return !$guest->guest_card_path;
            })->count();
        }

        return response()->json([
            'available_guests' => $availableGuests,
            'total_guests' => $allGuests->count(),
            'filtered_out_count' => count($existingGuestIds),
            'guests_without_cards' => $guestsWithoutCards,
            'notification_type' => $notificationType
        ]);
    }

    public function sendNotifications(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'notification_type' => 'required|in:SMS,WhatsApp,WHATSAPP',
            'guest_ids' => 'array',
            'guest_ids.*' => 'exists:guests,id'
        ]);

        // Normalize notification type to handle both WhatsApp and WHATSAPP
        if (strtoupper($validated['notification_type']) === 'WHATSAPP') {
            $validated['notification_type'] = 'WhatsApp';
        }

        $guests = $event->guests();
        
        if (!empty($validated['guest_ids'])) {
            $guests = $guests->whereIn('id', $validated['guest_ids']);
        }

        $guests = $guests->get();
        $notifications = [];
        $smsToNumbers = [];
        $phoneToNotificationId = [];

        foreach ($guests as $guest) {
            // For WhatsApp notifications, only include guests who have cards generated
            if ($validated['notification_type'] === 'WhatsApp' && !$guest->guest_card_path) {
                \Log::info('Skipping WhatsApp notification creation - guest has no card generated', [
                    'guest_id' => $guest->id,
                    'guest_name' => $guest->name,
                    'phone' => $guest->phone_number
                ]);
                continue;
            }

            // Replace template variables with actual guest details
            $personalizedMessage = $this->replaceTemplateVariables($validated['message'], $guest, $event);
            
            // Create notification based on type
            $notification = Notification::create([
                'guest_id' => $guest->id,
                'message' => $personalizedMessage,
                'notification_type' => $validated['notification_type'],
                'status' => 'Not Sent',
            ]);
            $notifications[] = $notification;
            
            // If SMS, collect phone numbers for bulk sending
            if ($validated['notification_type'] === 'SMS' && $guest->phone_number) {
                $smsToNumbers[$personalizedMessage][] = $guest->phone_number;
                $phoneToNotificationId[$guest->phone_number] = $notification->id;
            }
        }

        // Send SMS via NextSmsService if needed
        if ($validated['notification_type'] === 'SMS' && !empty($smsToNumbers)) {
            $messages = [];
            foreach ($smsToNumbers as $msg => $numbers) {
                $messages[] = [
                    'from' => 'KadiRafiki',
                    'to' => $numbers,
                    'text' => $msg,
                ];
            }
            $smsService = new NextSmsService();
            $reference = 'ref_' . time() . '_' . uniqid() . '_' . rand(1000, 9999);
            $result = $smsService->sendBulk($messages, $reference);
            
            if ($result && isset($result['messages'])) {
                foreach ($result['messages'] as $smsRes) {
                    $to = $smsRes['to'];
                    $statusName = $smsRes['status']['name'] ?? null;
                    $messageId = $smsRes['messageId'] ?? null;
                    
                    if (isset($phoneToNotificationId[$to]) && $statusName === 'PENDING_ENROUTE' && $messageId) {
                        $notification = Notification::find($phoneToNotificationId[$to]);
                        if ($notification) {
                            $notification->update([
                                'status' => 'Sent',
                                'sent_date' => now(),
                                'sms_reference' => $reference,
                                'message_id' => $messageId,
                            ]);
                        }
                    }
                }
            }
        }

        // Send WhatsApp messages if needed
        if ($validated['notification_type'] === 'WhatsApp') {
            $whatsappService = new WhatsAppService();
            $guestCardService = new GuestCardService();
            $phoneToNotificationId = [];

            foreach ($guests as $guest) {
                if ($guest->phone_number) {
                    // Find the notification for this guest
                    $notification = Notification::where('guest_id', $guest->id)
                                             ->where('notification_type', 'WhatsApp')
                                             ->where('status', 'Not Sent')
                                             ->first();
                    if ($notification) {
                        $phoneToNotificationId[$guest->phone_number] = $notification->id;
                    }
                }
            }

            // Send interactive template messages
            foreach ($guests as $guest) {
                if ($guest->phone_number) {
                    // Only send WhatsApp notifications to guests who have a card generated
                    if (!$guest->guest_card_path) {
                        \Log::info('Skipping WhatsApp notification - guest has no card generated', [
                            'guest_id' => $guest->id,
                            'guest_name' => $guest->name,
                            'phone' => $guest->phone_number
                        ]);
                        continue;
                    }

                    try {
                        $eventDate = $event->event_date ? \Carbon\Carbon::parse($event->event_date) : null;
                        
                        // Get the existing guest card URL (no need to generate new one)
                        $guestCardUrl = $guestCardService->generateGuestCard($guest, $event);
                        
                        // Generate Google Maps URL for the event
                        $googleMapsUrl = $event->generateGoogleMapsUrl();
                        
                        // Prepare template parameters with guest card as header
                        $templateParameters = [
                            [
                                'type' => 'header',
                                'parameters' => [
                                    [
                                        'type' => 'image',
                                        'image' => [
                                            'link' => $guestCardUrl
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $guest->name], // 1. Guest Name
                                    ['type' => 'text', 'text' => $event->event_name], // 2. Event Name
                                    ['type' => 'text', 'text' => $eventDate ? $eventDate->format('d/m/Y') : 'TBD'], // 3. Event Date
                                    ['type' => 'text', 'text' => $eventDate ? $eventDate->format('H:i') : 'TBD'], // 4. Event Time
                                    ['type' => 'text', 'text' => $event->event_location ?? 'TBD'], // 5. Location Name
                                    ['type' => 'text', 'text' => $guest->invite_code ?? 'KRGC123456'], // 6. Invite Code
                                    ['type' => 'text', 'text' => $guest->cardClass->name ?? 'VIP'] // 7. Card Class
                                ]
                            ],
                            [
                                'type' => 'button',
                                'sub_type' => 'quick_reply',
                                'index' => 0,
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'RSVP_YES'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'button',
                                'sub_type' => 'quick_reply',
                                'index' => 1,
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'RSVP_NO'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'button',
                                'sub_type' => 'quick_reply',
                                'index' => 2,
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'RSVP_MAYBE'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'button',
                                'sub_type' => 'url',
                                'index' => 3,
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $googleMapsUrl
                                    ]
                                ]
                            ]
                        ];

                        // Use the guest wedding invitation template
                        $templateName = 'guest_wedding_invitation';
                        
                        $result = $whatsappService->sendInteractiveTemplateMessage(
                            $guest->phone_number, 
                            $templateName, 
                            $templateParameters
                        );
                        
                        if ($result['success'] && isset($phoneToNotificationId[$guest->phone_number])) {
                            $notification = Notification::find($phoneToNotificationId[$guest->phone_number]);
                            
                            if ($notification) {
                                $notification->update([
                                    'status' => 'Sent',
                                    'sent_date' => now(),
                                    'message_id' => $result['message_id'] ?? null,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to send WhatsApp message to guest', [
                            'guest_id' => $guest->id,
                            'phone' => $guest->phone_number,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Continue with other guests even if one fails
                        continue;
                    }
                }
            }
        }
        
        // Update event status
        EventStatusService::updateEventStatus($event);
        
        // Count guests filtered out due to missing cards (for WhatsApp only)
        $guestsWithoutCards = 0;
        if ($validated['notification_type'] === 'WhatsApp') {
            $guestsWithoutCards = $guests->filter(function ($guest) {
                return !$guest->guest_card_path;
            })->count();
        }
        
        $message = count($notifications) . ' notifications created and ' . $validated['notification_type'] . ' sent';
        if ($validated['notification_type'] === 'WhatsApp' && $guestsWithoutCards > 0) {
            $message .= ' (' . $guestsWithoutCards . ' guests skipped - no cards generated)';
        }
        
        return response()->json([
            'message' => $message,
            'notifications' => $notifications,
            'guests_without_cards' => $guestsWithoutCards
        ], 201);
    }

    private function replaceTemplateVariables(string $template, Guest $guest, Event $event): string
    {
        $eventDate = $event->event_date ? \Carbon\Carbon::parse($event->event_date) : null;
        
        return str_replace(
            [
                '{guest_name}',
                '{event_name}',
                '{event_date}',
                '{event_time}',
                '{event_location}',
                '{invite_code}',
                '{rsvp_url}',
                '{mpesa_number}',
                '{airtel_number}'
            ],
            [
                $guest->name,
                $event->event_name,
                $eventDate ? $eventDate->format('d/m/Y') : 'TBD',
                $eventDate ? $eventDate->format('H:i') : 'TBD',
                $event->event_location ?? 'TBD',
                $guest->invite_code ?? 'KRGC123456',
                'https://kadirafiki.com/rsvp/' . ($guest->invite_code ?? 'KRGC123456'),
                '+255700000000',
                '+255750000000'
            ],
            $template
        );
    }

    private function getWhatsAppTemplateName(Event $event): string
    {
        $eventType = strtolower($event->eventType->name ?? 'wedding');
        
        // Map event types to template names
        $templateMap = [
            'wedding' => 'wedding_invitation_interactive',
            'birthday' => 'birthday_invitation_interactive',
            'graduation' => 'graduation_invitation_interactive',
            'anniversary' => 'anniversary_invitation_interactive',
            'corporate event' => 'corporate_event_interactive',
            'conference' => 'conference_invitation_interactive',
            'seminar' => 'seminar_invitation_interactive',
            'workshop' => 'workshop_invitation_interactive',
            'send-off' => 'sendoff_invitation_interactive',
            'baby shower' => 'babyshower_invitation_interactive'
        ];
        
        return $templateMap[$eventType] ?? 'wedding_invitation_interactive';
    }

    public function markAsSent(Notification $notification): JsonResponse
    {
        $notification->update([
            'status' => 'Sent',
            'sent_date' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as sent',
            'notification' => $notification->load('guest')
        ]);
    }

    public function markAsNotSent(Notification $notification): JsonResponse
    {
        $notification->update([
            'status' => 'Not Sent',
            'sent_date' => null,
        ]);

        return response()->json([
            'message' => 'Notification marked as not sent',
            'notification' => $notification->load('guest')
        ]);
    }
}
