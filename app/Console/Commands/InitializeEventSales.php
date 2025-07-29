<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\SalesService;
use Illuminate\Console\Command;

class InitializeEventSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:initialize-sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize sales records for existing events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to initialize sales records for existing events...');

        $events = Event::all();
        $count = 0;

        foreach ($events as $event) {
            try {
                SalesService::updateEventSales($event);
                $count++;
                $this->line("✓ Created sales record for event: {$event->event_name} (ID: {$event->id})");
            } catch (\Exception $e) {
                $this->error("✗ Failed to create sales record for event {$event->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed! Created sales records for {$count} events.");
    }
}
