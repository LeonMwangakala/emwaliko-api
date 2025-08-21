<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventScanner;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateScannerAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scanners:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing scanner assignments from old system to new event_scanners table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scanner assignments migration...');

        // Get all events with scanner_person field
        $events = Event::whereNotNull('scanner_person')
                      ->where('scanner_person', '!=', '')
                      ->get();

        if ($events->isEmpty()) {
            $this->info('No events with scanner assignments found to migrate.');
            return;
        }

        $this->info("Found {$events->count()} events with scanner assignments to migrate.");

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($events as $event) {
            $this->line("Processing event: {$event->event_name}");

            // Try to find user by name
            $user = User::where('name', $event->scanner_person)->first();

            if (!$user) {
                $this->warn("  - User '{$event->scanner_person}' not found, skipping...");
                $skippedCount++;
                continue;
            }

            // Check if assignment already exists
            $existingAssignment = EventScanner::where('event_id', $event->id)
                                            ->where('user_id', $user->id)
                                            ->first();

            if ($existingAssignment) {
                $this->line("  - Assignment already exists for user '{$user->name}', skipping...");
                $skippedCount++;
                continue;
            }

            // Create new assignment
            EventScanner::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'role' => 'primary', // Default to primary for existing assignments
                'is_active' => true,
                'assigned_at' => now(),
            ]);

            $this->info("  - Successfully assigned '{$user->name}' as primary scanner");
            $migratedCount++;
        }

        $this->newLine();
        $this->info("Migration completed!");
        $this->info("  - Migrated: {$migratedCount} assignments");
        $this->info("  - Skipped: {$skippedCount} assignments");

        if ($migratedCount > 0) {
            $this->warn("\nNote: You may want to remove the old 'scanner_person' column from the events table after verifying the migration.");
        }
    }
}
