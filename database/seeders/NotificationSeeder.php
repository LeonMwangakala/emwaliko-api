<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\Guest;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guests = Guest::all();
        
        foreach ($guests as $guest) {
            Notification::create([
                'guest_id' => $guest->id,
                'message' => 'You are cordially invited to attend the event.',
                'whatsapp_notifications' => rand(0, 1),
                'sms_notifications' => rand(0, 1),
                'sent_at' => now()->subDays(rand(1, 7)),
            ]);
        }
    }
} 