<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GuestCardCleanupService
{
    /**
     * Clean up all guest cards for an event
     */
    public static function cleanupEventGuestCards(Event $event): array
    {
        $results = [
            'total_guests' => 0,
            'cards_deleted' => 0,
            'paths_cleared' => 0,
            'errors' => [],
            'deleted_files' => []
        ];

        try {
            Log::info('Starting guest card cleanup for event', [
                'event_id' => $event->id,
                'event_name' => $event->event_name
            ]);

            // Get all guests for this event
            $guests = $event->guests()->whereNotNull('guest_card_path')->get();
            $results['total_guests'] = $guests->count();

            if ($guests->isEmpty()) {
                Log::info('No guest cards found to clean up', [
                    'event_id' => $event->id
                ]);
                return $results;
            }

            foreach ($guests as $guest) {
                try {
                    $cardPath = $guest->guest_card_path;
                    
                    if (!$cardPath) {
                        continue;
                    }

                    // Delete the physical file
                    $fullPath = storage_path('app/public/' . $cardPath);
                    
                    if (file_exists($fullPath)) {
                        if (unlink($fullPath)) {
                            $results['cards_deleted']++;
                            $results['deleted_files'][] = $cardPath;
                            
                            Log::info('Guest card file deleted', [
                                'event_id' => $event->id,
                                'guest_id' => $guest->id,
                                'guest_name' => $guest->name,
                                'file_path' => $cardPath
                            ]);
                        } else {
                            $results['errors'][] = "Failed to delete file: {$cardPath}";
                            Log::error('Failed to delete guest card file', [
                                'event_id' => $event->id,
                                'guest_id' => $guest->id,
                                'file_path' => $cardPath
                            ]);
                        }
                    } else {
                        Log::warning('Guest card file not found on disk', [
                            'event_id' => $event->id,
                            'guest_id' => $guest->id,
                            'file_path' => $cardPath
                        ]);
                    }

                    // Clear the guest_card_path in database
                    $guest->update(['guest_card_path' => null]);
                    $results['paths_cleared']++;

                    Log::info('Guest card path cleared from database', [
                        'event_id' => $event->id,
                        'guest_id' => $guest->id,
                        'guest_name' => $guest->name
                    ]);

                } catch (\Exception $e) {
                    $error = "Error processing guest {$guest->id} ({$guest->name}): " . $e->getMessage();
                    $results['errors'][] = $error;
                    
                    Log::error('Error during guest card cleanup', [
                        'event_id' => $event->id,
                        'guest_id' => $guest->id,
                        'guest_name' => $guest->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Guest card cleanup completed for event', [
                'event_id' => $event->id,
                'event_name' => $event->event_name,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            $error = "General cleanup error: " . $e->getMessage();
            $results['errors'][] = $error;
            
            Log::error('General error during guest card cleanup', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Clean up guest cards for multiple events
     */
    public static function cleanupMultipleEventGuestCards(array $eventIds): array
    {
        $results = [
            'events_processed' => 0,
            'total_cards_deleted' => 0,
            'total_paths_cleared' => 0,
            'errors' => []
        ];

        foreach ($eventIds as $eventId) {
            try {
                $event = Event::find($eventId);
                
                if (!$event) {
                    $results['errors'][] = "Event not found: {$eventId}";
                    continue;
                }

                $eventResults = self::cleanupEventGuestCards($event);
                
                $results['events_processed']++;
                $results['total_cards_deleted'] += $eventResults['cards_deleted'];
                $results['total_paths_cleared'] += $eventResults['paths_cleared'];
                $results['errors'] = array_merge($results['errors'], $eventResults['errors']);

            } catch (\Exception $e) {
                $results['errors'][] = "Error processing event {$eventId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get cleanup statistics for an event
     */
    public static function getEventCleanupStats(Event $event): array
    {
        $guestsWithCards = $event->guests()->whereNotNull('guest_card_path')->count();
        $totalGuests = $event->guests()->count();
        
        $cardFiles = [];
        $totalSize = 0;
        
        if ($guestsWithCards > 0) {
            $guests = $event->guests()->whereNotNull('guest_card_path')->get();
            
            foreach ($guests as $guest) {
                $fullPath = storage_path('app/public/' . $guest->guest_card_path);
                
                if (file_exists($fullPath)) {
                    $fileSize = filesize($fullPath);
                    $totalSize += $fileSize;
                    
                    $cardFiles[] = [
                        'guest_name' => $guest->name,
                        'file_path' => $guest->guest_card_path,
                        'file_size' => $fileSize,
                        'file_size_mb' => round($fileSize / 1024 / 1024, 2)
                    ];
                }
            }
        }

        return [
            'event_id' => $event->id,
            'event_name' => $event->event_name,
            'total_guests' => $totalGuests,
            'guests_with_cards' => $guestsWithCards,
            'total_card_files' => count($cardFiles),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'card_files' => $cardFiles
        ];
    }
}
