<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy position data from card_types to events
        $events = DB::table('events')->get();
        
        foreach ($events as $event) {
            $cardType = DB::table('card_types')->where('id', $event->card_type_id)->first();
            
            if ($cardType) {
                DB::table('events')
                    ->where('id', $event->id)
                    ->update([
                        'name_position_x' => $cardType->name_position_x ?? 50.00,
                        'name_position_y' => $cardType->name_position_y ?? 30.00,
                        'qr_position_x' => $cardType->qr_position_x ?? 80.00,
                        'qr_position_y' => $cardType->qr_position_y ?? 70.00,
                        'card_class_position_x' => $cardType->card_class_position_x ?? 20.00,
                        'card_class_position_y' => $cardType->card_class_position_y ?? 90.00,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we're moving data
        // The reverse would require copying data back to card_types
        // which would be complex and potentially lossy
    }
};
