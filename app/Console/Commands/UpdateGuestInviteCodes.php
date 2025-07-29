<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Guest;

class UpdateGuestInviteCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:update-invite-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all guests to have invite codes in the KRGC + 6-digit pattern';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $guests = Guest::all();
        $updated = 0;
        foreach ($guests as $guest) {
            // Skip if already matches the pattern
            if (preg_match('/^KRGC\d{6}$/', $guest->invite_code)) {
                continue;
            }
            // Generate a unique code
            do {
                $code = 'KRGC' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (Guest::where('invite_code', $code)->exists());
            $guest->invite_code = $code;
            $guest->save();
            $updated++;
            $this->info("Updated guest #{$guest->id} to code {$code}");
        }
        $this->info("Done. Updated $updated guests.");
    }
}
