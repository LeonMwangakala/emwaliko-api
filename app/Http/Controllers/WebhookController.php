<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function verify(Request $request)
    {
        // Try both dot and underscore formats
        $mode = $request->get('hub.mode') ?: $request->get('hub_mode');
        $token = $request->get('hub.verify_token') ?: $request->get('hub_verify_token');
        $challenge = $request->get('hub.challenge') ?: $request->get('hub_challenge');

        $webhookVerifyToken = config('services.whatsapp.webhook_verify_token');

        Log::info('Webhook verification attempt', [
            'mode' => $mode,
            'token_received' => $token,
            'token_expected' => $webhookVerifyToken,
            'challenge' => $challenge,
            'all_params' => $request->all()
        ]);

        if ($mode === 'subscribe' && $token === $webhookVerifyToken) {
            Log::info('WhatsApp webhook verified successfully');
            // Return plain text response as required by Facebook
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::error('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $webhookVerifyToken,
            'all_request_params' => $request->all()
        ]);

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-Hub-Signature-256');
            $body = $request->getContent();

            $whatsappService = new WhatsAppService();

            // Verify webhook signature
            if (!$whatsappService->verifyWebhookSignature($signature, $body)) {
                Log::error('WhatsApp webhook signature verification failed', [
                    'signature' => $signature,
                    'body_length' => strlen($body)
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $payload = $request->json()->all();
            
            // Process the webhook
            $result = $whatsappService->processWebhook($payload);

            if ($result['success']) {
                Log::info('WhatsApp webhook processed successfully', [
                    'processed' => $result['processed'],
                    'results' => $result['results']
                ]);

                return response()->json([
                    'success' => true,
                    'processed' => $result['processed']
                ]);
            } else {
                Log::error('WhatsApp webhook processing failed', [
                    'error' => $result['error'],
                    'payload' => $payload
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        try {
            $phone = $request->input('phone');
            $message = $request->input('message', 'Test message from Kadirafiki');
            
            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Phone number is required'
                ], 400);
            }
            
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->sendMessage($phone, $message);
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['success'] 
                    ? 'Message sent successfully. Check your WhatsApp.' 
                    : 'Failed to send message. Check error details.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testTemplateWhatsApp(Request $request): JsonResponse
    {
        try {
            $phone = $request->input('phone');
            $templateName = $request->input('template', 'hello_world');
            
            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Phone number is required'
                ], 400);
            }
            
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->sendTemplateMessage($phone, $templateName);
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['success'] 
                    ? 'Template message sent successfully. Check your WhatsApp.' 
                    : 'Failed to send template message. Check error details.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testInteractiveWhatsApp(Request $request): JsonResponse
    {
        try {
            $phone = $request->input('phone');
            $message = $request->input('message', 'Hello! You are invited to our event. Please RSVP:');
            
            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Phone number is required'
                ], 400);
            }
            
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->sendInteractiveMessage($phone, $message);
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['success'] 
                    ? 'Interactive message sent successfully with RSVP buttons. Check your WhatsApp.' 
                    : 'Failed to send interactive message. Check error details.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testInteractiveTemplateWhatsApp(Request $request): JsonResponse
    {
        try {
            $phone = $request->input('phone');
            $templateName = $request->input('template', 'wedding_invitation_interactive');
            $parameters = $request->input('parameters', []);
            
            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Phone number is required'
                ], 400);
            }
            
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->sendInteractiveTemplateMessage($phone, $templateName, $parameters);
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['success'] 
                    ? 'Interactive template message sent successfully with RSVP buttons. Check your WhatsApp.' 
                    : 'Failed to send interactive template message. Check error details.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testGuestCardGeneration(Request $request): JsonResponse
    {
        try {
            $guestId = $request->input('guest_id');
            $eventId = $request->input('event_id');
            
            if (!$guestId || !$eventId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Guest ID and Event ID are required'
                ], 400);
            }
            
            $guest = \App\Models\Guest::find($guestId);
            $event = \App\Models\Event::find($eventId);
            
            if (!$guest || !$event) {
                return response()->json([
                    'success' => false,
                    'error' => 'Guest or Event not found'
                ], 400);
            }
            
            $guestCardService = new \App\Services\GuestCardService();
            $guestCardBase64 = $guestCardService->generateGuestCard($guest, $event);
            
            return response()->json([
                'success' => true,
                'message' => 'Guest card generated successfully',
                'guest_name' => $guest->name,
                'event_name' => $event->event_name,
                'card_base64_length' => strlen($guestCardBase64),
                'card_preview' => 'data:image/png;base64,' . substr($guestCardBase64, 0, 100) . '...'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testGuestWeddingInvitation(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string',
                'guest_id' => 'required|exists:guests,id',
                'event_id' => 'required|exists:events,id'
            ]);

            $guest = \App\Models\Guest::with(['cardClass'])->find($validated['guest_id']);
            $event = \App\Models\Event::find($validated['event_id']);
            
            if (!$guest || !$event) {
                return response()->json([
                    'success' => false,
                    'error' => 'Guest or Event not found'
                ], 404);
            }

            $whatsappService = new WhatsAppService();
            $guestCardService = new \App\Services\GuestCardService();

            // Generate personalized guest card
            $guestCardUrl = $guestCardService->generateGuestCard($guest, $event);
            
            $eventDate = $event->event_date ? \Carbon\Carbon::parse($event->event_date) : null;

            // Generate Google Maps URL for the event
            $googleMapsUrl = $event->generateGoogleMapsUrl();
            
            // Prepare parameters for guest_wedding_invitation template
            $parameters = [
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
                        ['type' => 'text', 'text' => $guest->cardClass->name ?? 'VIP'], // 7. Card Class
                        ['type' => 'text', 'text' => $googleMapsUrl] // 8. Google Maps URL
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
                ]
            ];

            $result = $whatsappService->sendInteractiveTemplateMessage(
                $validated['phone'],
                'guest_wedding_invitation',
                $parameters
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Guest wedding invitation sent successfully' : 'Failed to send invitation',
                'guest_name' => $guest->name,
                'event_name' => $event->event_name,
                'card_class' => $guest->cardClass->name ?? 'Standard',
                'invite_code' => $guest->invite_code,
                'guest_card_url' => $guestCardUrl,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Test guest wedding invitation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listWhatsAppTemplates()
    {
        try {
            $whatsappService = new WhatsAppService();
            
            // Try to get templates from WhatsApp API
            $url = "https://graph.facebook.com/v18.0/{$whatsappService->getPhoneNumberId()}/message_templates?access_token={$whatsappService->getAccessToken()}";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$whatsappService->getAccessToken()}",
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'templates' => $data['data'] ?? [],
                    'total' => count($data['data'] ?? [])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $response->json(),
                    'status' => $response->status()
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
