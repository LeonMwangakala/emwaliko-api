<?php

require_once 'vendor/autoload.php';

use App\Services\GuestCardService;
use App\Services\WhatsAppService;
use App\Models\Guest;
use App\Models\Event;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing WhatsApp Notification with Guest Card ===\n\n";

try {
    // Get a test guest and event
    $guest = Guest::with('cardClass')->find(1);
    $event = Event::with('cardType')->find(1);
    
    if (!$guest) {
        echo "❌ No guest found with ID 1\n";
        exit(1);
    }
    
    if (!$event) {
        echo "❌ No event found with ID 1\n";
        exit(1);
    }
    
    if (!$guest->phone_number) {
        echo "❌ Guest has no phone number\n";
        exit(1);
    }
    
    echo "✅ Found guest: {$guest->name}\n";
    echo "✅ Found event: {$event->event_name}\n";
    echo "✅ Guest phone: {$guest->phone_number}\n";
    echo "✅ Card type: {$event->cardType->name}\n\n";
    
    // Generate guest card
    $guestCardService = new GuestCardService();
    
    echo "🔄 Generating guest card...\n";
    $guestCardUrl = $guestCardService->generateGuestCard($guest, $event);
    echo "✅ Guest card generated: {$guestCardUrl}\n\n";
    
    // Initialize WhatsApp service
    $whatsappService = new WhatsAppService();
    
    // Prepare template parameters (same as NotificationController)
    $eventDate = $event->event_date ? \Carbon\Carbon::parse($event->event_date) : null;
    
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
                    'text' => 'View Location'
                ]
            ]
        ]
    ];
    
    echo "📱 Sending WhatsApp notification with guest card...\n";
    echo "📞 To: {$guest->phone_number}\n";
    echo "🎨 Card: {$guestCardUrl}\n";
    echo "👤 Guest: {$guest->name}\n";
    echo "🎉 Event: {$event->event_name}\n\n";
    
    // Send the WhatsApp notification
    $response = $whatsappService->sendInteractiveTemplateMessage(
        $guest->phone_number,
        'event_invitation_template', // Template name
        $templateParameters
    );
    
    echo "📤 WhatsApp API Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($response['error'])) {
        echo "❌ WhatsApp notification failed:\n";
        echo "   Error: " . $response['error']['message'] . "\n";
        echo "   Code: " . $response['error']['code'] . "\n";
    } else {
        echo "✅ WhatsApp notification sent successfully!\n";
        echo "📱 Message ID: " . ($response['messages'][0]['id'] ?? 'N/A') . "\n";
        echo "📞 Phone: {$guest->phone_number}\n";
        echo "🎨 Guest card included as header image\n";
    }
    
    echo "\n=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📝 Stack trace:\n" . $e->getTraceAsString() . "\n";
}
