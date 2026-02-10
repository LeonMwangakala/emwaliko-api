<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;

class UpdateEventCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:update-event-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all events to have event codes in the EMEC + 6-digit pattern';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $events = Event::all();
        $updated = 0;
        foreach ($events as $event) {
            // Skip if already matches the new pattern
            if (preg_match('/^EMEC\d{6}$/', $event->event_code)) {
                continue;
            }
            // Generate a unique code
            do {
                $code = 'EMEC' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (Event::where('event_code', $code)->exists());
            $event->event_code = $code;
            $event->save();
            $updated++;
            $this->info("Updated event #{$event->id} to code {$code}");
        }
        $this->info("Done. Updated $updated events.");
    }
}
