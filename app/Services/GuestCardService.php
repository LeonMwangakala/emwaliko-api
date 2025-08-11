<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Event;
use App\Models\CardType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GuestCardService
{
    public function generateGuestCard(Guest $guest, Event $event): string
    {
        try {
            // Get card type configuration
            $cardType = CardType::find($event->card_type_id);
            if (!$cardType) {
                throw new \Exception('Card type not found');
            }

            // Get card design image
            if (!$event->card_design_path) {
                throw new \Exception('Card design not found');
            }

            $cardDesignPath = storage_path('app/public/' . $event->card_design_path);
            if (!file_exists($cardDesignPath)) {
                throw new \Exception('Card design file not found');
            }

            // Create image manager
            $manager = new ImageManager(new Driver());
            
            // Create image from card design
            $image = $manager->read($cardDesignPath);
            
            // Resize to a reasonable size for WhatsApp (max 5MB)
            $image->resize(1200, 1200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // Get QR code image
            $qrCodeImage = $this->getQrCodeImage($guest, $manager);
            
            // Get guest name and card class
            $guestName = $guest->name;
            $cardClassName = $guest->cardClass->name ?? '';

            // Add guest name if enabled
            if ($cardType->show_guest_name) {
                $this->addTextToImage(
                    $image, 
                    $guestName, 
                    $cardType->name_position_x, 
                    $cardType->name_position_y, 
                    98, // font size
                    '#000000'
                );
            }

            // Add QR code if enabled
            if ($qrCodeImage) {
                $image->place($qrCodeImage, $cardType->qr_position_x - 300, $cardType->qr_position_y - 300);
            }

            // Add card class if enabled
            if ($cardType->show_card_class && $cardClassName) {
                $this->addTextToImage(
                    $image, 
                    $cardClassName, 
                    $cardType->card_class_position_x, 
                    $cardType->card_class_position_y, 
                    60, // font size
                    '#333333'
                );
            }

            // Generate unique filename for this guest card
            $filename = 'guest_cards/' . $guest->invite_code . '_' . time() . '.png';
            $fullPath = storage_path('app/public/' . $filename);
            
            // Ensure directory exists
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save the generated card with compression
            $image->save($fullPath, 80); // 80% quality

            // Return the public URL for WhatsApp
            return url('storage/' . $filename);

        } catch (\Exception $e) {
            Log::error('Failed to generate guest card', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function getQrCodeImage(Guest $guest, ImageManager $manager): ?\Intervention\Image\Image
    {
        try {
            if ($guest->qr_code_path) {
                $qrPath = storage_path('app/public/' . $guest->qr_code_path);
                if (file_exists($qrPath)) {
                    return $manager->read($qrPath)->resize(600, 600);
                }
            }

            // Generate QR code if not exists
            $qrCode = QrCode::format('png')
                ->size(600)
                ->margin(10)
                ->errorCorrection('M')
                ->generate(route('guest.rsvp', $guest->invite_code));

            return $manager->read($qrCode);

        } catch (\Exception $e) {
            Log::error('Failed to get QR code image', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function addTextToImage($image, string $text, int $x, int $y, int $fontSize, string $color): void
    {
        // Add text shadow effect
        $image->text($text, $x + 2, $y + 2, function ($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });

        // Add main text
        $image->text($text, $x, $y, function ($font) use ($fontSize, $color) {
            $font->size($fontSize);
            $font->color($color);
            $font->align('center');
            $font->valign('middle');
        });
    }
}
