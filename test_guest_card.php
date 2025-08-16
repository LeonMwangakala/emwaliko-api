<?php

require_once 'vendor/autoload.php';

use App\Services\GuestCardService;
use App\Models\Guest;
use App\Models\Event;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing GuestCardService ===\n\n";

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
    
    echo "✅ Found guest: {$guest->name}\n";
    echo "✅ Found event: {$event->event_name}\n";
    echo "✅ Card design path: {$event->card_design_path}\n";
    echo "✅ Card type: {$event->cardType->name}\n";
    echo "✅ Guest card class: {$guest->cardClass->name}\n\n";
    
    // Test the GuestCardService
    $guestCardService = new GuestCardService();
    
    echo "🔄 Generating guest card...\n";
    $startTime = microtime(true);
    
    $guestCardUrl = $guestCardService->generateGuestCard($guest, $event);
    
    $endTime = microtime(true);
    $generationTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "✅ Guest card generated successfully!\n";
    echo "⏱️  Generation time: {$generationTime}ms\n";
    echo "🔗 Card URL: {$guestCardUrl}\n\n";
    
    // Test if the file exists
    $filename = str_replace(url('storage/'), '', $guestCardUrl);
    $fullPath = storage_path('app/public/' . $filename);
    
    if (file_exists($fullPath)) {
        $fileSize = filesize($fullPath);
        $fileSizeKB = round($fileSize / 1024, 2);
        echo "✅ Card file exists: {$fullPath}\n";
        echo "📁 File size: {$fileSizeKB} KB\n";
        
        // Check if file is accessible via HTTP
        $headers = get_headers($guestCardUrl);
        if ($headers && strpos($headers[0], '200') !== false) {
            echo "✅ Card is accessible via HTTP\n";
        } else {
            echo "❌ Card is NOT accessible via HTTP\n";
        }
        
    } else {
        echo "❌ Card file does not exist: {$fullPath}\n";
    }
    
    echo "\n=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📝 Stack trace:\n" . $e->getTraceAsString() . "\n";
}
