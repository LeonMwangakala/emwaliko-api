<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class EventStatusService
{
    /**
     * Update event status based on current state
     */
    public static function updateEventStatus(Event $event): void
    {
        // Don't update if event is cancelled
        if ($event->status === 'cancelled') {
            return;
        }

        $newStatus = self::determineEventStatus($event);
        
        if ($newStatus !== $event->status) {
            $event->update(['status' => $newStatus]);
        }
    }

    /**
     * Determine the appropriate status for an event
     */
    public static function determineEventStatus(Event $event): string
    {
        // Check if event can be marked as completed (must be invoiced)
        if (SalesService::canMarkEventAsCompleted($event)) {
            return 'completed';
        }

        // Count guests, notifications, and scans
        $guestCount = $event->guests()->count();
        $notificationCount = $event->notifications()->where('status', 'Sent')->count();
        $scanCount = $event->scans()->where('status', 'scanned')->count();

        // No guests - initiated
        if ($guestCount === 0) {
            return 'initiated';
        }

        // Has guests but no notifications sent - inprogress
        if ($notificationCount === 0) {
            return 'inprogress';
        }

        // Has notifications but no scans - notified
        if ($scanCount === 0) {
            return 'notified';
        }

        // Has scans - scanned
        return 'scanned';
    }

    /**
     * Update status for all events (useful for scheduled tasks)
     */
    public static function updateAllEventStatuses(): void
    {
        $events = Event::where('status', '!=', 'cancelled')->get();
        
        foreach ($events as $event) {
            self::updateEventStatus($event);
        }
    }

    /**
     * Check if an event can be updated (not completed or cancelled)
     */
    public static function canUpdateEvent(Event $event): bool
    {
        return $event->status !== 'completed' && $event->status !== 'cancelled';
    }

    /**
     * Mark event as completed (only if invoiced)
     */
    public static function markEventAsCompleted(Event $event): bool
    {
        if (SalesService::canMarkEventAsCompleted($event)) {
            $event->update(['status' => 'completed']);
            return true;
        }
        
        return false;
    }
} 