<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\EventStatusService;
use Illuminate\Console\Command;

class UpdateEventStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:update-status 
                            {--event-id= : Update specific event by ID}
                            {--event-code= : Update specific event by event code}
                            {--status= : New status to set (initiated, inprogress, notified, scanned, completed, cancelled)}
                            {--auto : Automatically determine and update status based on event data}
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Force update even if status validation fails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update event statuses with various options';

    /**
     * Valid event statuses
     */
    private array $validStatuses = [
        'initiated',
        'inprogress', 
        'notified',
        'scanned',
        'completed',
        'cancelled'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->option('event-id');
        $eventCode = $this->option('event-code');
        $status = $this->option('status');
        $auto = $this->option('auto');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Validate inputs
        if (!$this->validateInputs($eventId, $eventCode, $status, $auto)) {
            return 1;
        }

        // Get events to update
        $events = $this->getEventsToUpdate($eventId, $eventCode);
        
        if ($events->isEmpty()) {
            $this->error('No events found matching the criteria.');
            return 1;
        }

        $this->info("Found {$events->count()} event(s) to process.");

        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($events as $event) {
            $this->line("Processing event: {$event->event_name} (ID: {$event->id}, Current Status: {$event->status})");

            try {
                $newStatus = $this->determineNewStatus($event, $status, $auto);
                
                if ($newStatus === null) {
                    $this->warn("  Skipped: Could not determine new status");
                    $skippedCount++;
                    continue;
                }

                if ($newStatus === $event->status) {
                    $this->info("  No change needed: Status is already '{$newStatus}'");
                    $skippedCount++;
                    continue;
                }

                // Validate status transition unless forced
                if (!$force && !$this->validateStatusTransition($event->status, $newStatus)) {
                    $this->warn("  Skipped: Invalid status transition from '{$event->status}' to '{$newStatus}'");
                    $skippedCount++;
                    continue;
                }

                if ($dryRun) {
                    $this->info("  Would update: {$event->status} → {$newStatus}");
                    $updatedCount++;
                } else {
                    $oldStatus = $event->status;
                    $event->update(['status' => $newStatus]);
                    
                    // Update related data if needed
                    if ($newStatus === 'scanned') {
                        $this->updateScannedStatus($event);
                    }
                    
                    $this->info("  Updated: {$oldStatus} → {$newStatus}");
                    $updatedCount++;
                }

            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Summary
        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Total events processed: {$events->count()}");
        $this->info("Updated: {$updatedCount}");
        $this->info("Skipped: {$skippedCount}");
        $this->info("Errors: {$errorCount}");

        if ($dryRun) {
            $this->warn("This was a dry run. No changes were made.");
        }

        return 0;
    }

    /**
     * Validate command inputs
     */
    private function validateInputs($eventId, $eventCode, $status, $auto): bool
    {
        // Check if we have a way to identify events
        if (!$eventId && !$eventCode && !$auto) {
            $this->error('You must specify either --event-id, --event-code, or --auto');
            return false;
        }

        // Validate status if provided
        if ($status && !in_array($status, $this->validStatuses)) {
            $this->error("Invalid status '{$status}'. Valid statuses: " . implode(', ', $this->validStatuses));
            return false;
        }

        // Check for conflicting options
        if ($status && $auto) {
            $this->error('Cannot use both --status and --auto options');
            return false;
        }

        return true;
    }

    /**
     * Get events to update based on criteria
     */
    private function getEventsToUpdate($eventId, $eventCode)
    {
        $query = Event::query();

        if ($eventId) {
            $query->where('id', $eventId);
        }

        if ($eventCode) {
            $query->where('event_code', $eventCode);
        }

        return $query->get();
    }

    /**
     * Determine the new status for an event
     */
    private function determineNewStatus(Event $event, $status, $auto): ?string
    {
        if ($status) {
            return $status;
        }

        if ($auto) {
            return $this->autoDetermineStatus($event);
        }

        return null;
    }

    /**
     * Automatically determine event status based on event data
     */
    private function autoDetermineStatus(Event $event): string
    {
        $guestCount = $event->guests()->count();
        $notificationCount = $event->notifications()->count();
        $scannedCount = $event->scans()->where('status', 'scanned')->count();
        $hasSales = $event->sales()->exists();
        $salesStatus = $hasSales ? $event->sales()->first()->status : null;

        // Check if event is cancelled
        if ($event->status === 'cancelled') {
            return 'cancelled';
        }

        // Check if event is completed (based on sales status and date)
        if ($salesStatus === 'Paid' && $event->event_date < now()) {
            return 'completed';
        }

        // Check if guests have been scanned
        if ($scannedCount > 0) {
            return 'scanned';
        }

        // Check if notifications have been sent
        if ($notificationCount > 0) {
            return 'notified';
        }

        // Check if event has guests
        if ($guestCount > 0) {
            return 'inprogress';
        }

        // Default to initiated
        return 'initiated';
    }

    /**
     * Validate status transition
     */
    private function validateStatusTransition(string $currentStatus, string $newStatus): bool
    {
        // Define valid transitions
        $validTransitions = [
            'initiated' => ['inprogress', 'cancelled'],
            'inprogress' => ['notified', 'cancelled'],
            'notified' => ['scanned', 'cancelled'],
            'scanned' => ['completed', 'cancelled'],
            'completed' => [], // No further transitions
            'cancelled' => [], // No further transitions
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    /**
     * Update related data when status changes to scanned
     */
    private function updateScannedStatus(Event $event): void
    {
        // Update scan records to mark them as scanned if they exist
        $event->scans()->update(['status' => 'scanned']);
        
        // Update event status using the service
        EventStatusService::updateEventStatus($event);
    }
}
