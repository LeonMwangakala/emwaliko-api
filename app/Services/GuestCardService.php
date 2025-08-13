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

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // For WhatsApp compatibility, use a fixed size that works well
            // Card dimensions optimized for WhatsApp display
            $targetWidth = 750;
            $targetHeight = 1050;

            // Resize the card to fit WhatsApp requirements
            $image->resize($targetWidth, $targetHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Calculate scale factors for positioning
            $scaleX = $targetWidth / $originalWidth;
            $scaleY = $targetHeight / $originalHeight;
            $scale = min($scaleX, $scaleY); // Use the smaller scale to maintain proportions

            // Get guest name and card class
            $guestName = $guest->name;
            $cardClassName = $guest->cardClass->name ?? '';

            // Add guest name if enabled
            if ($cardType->show_guest_name) {
                $fontSize = max(72, (int)(144 * $scale)); // Increased by 3x: minimum 72px, scale from 144px base
                
                // Convert percentage positions to absolute pixels
                $nameX = (int)(($cardType->name_position_x / 100) * $targetWidth);
                $nameY = (int)(($cardType->name_position_y / 100) * $targetHeight);
                
                $this->addTextToImage(
                    $image,
                    $guestName,
                    $nameX,
                    $nameY,
                    $fontSize,
                    '#FFFFFF'
                );
            }

            // Add QR code if enabled
            if ($cardType->show_qr_code) {
                $qrCodeImage = $this->getQrCodeImage($guest, $manager, $scale);
                if ($qrCodeImage) {
                    $qrSize = (int)(150 * $scale); // Increased QR size for better visibility
                    
                    // Convert percentage positions to absolute pixels
                    $qrX = (int)(($cardType->qr_position_x / 100) * $targetWidth - $qrSize/2);
                    $qrY = (int)(($cardType->qr_position_y / 100) * $targetHeight - $qrSize/2);
                    
                    $image->place($qrCodeImage, $qrX, $qrY);
                }
            }

            // Add card class if enabled
            if ($cardType->show_card_class && $cardClassName) {
                $fontSize = max(54, (int)(108 * $scale)); // Increased by 3x: minimum 54px, scale from 108px base
                
                // Convert percentage positions to absolute pixels
                $classX = (int)(($cardType->card_class_position_x / 100) * $targetWidth);
                $classY = (int)(($cardType->card_class_position_y / 100) * $targetHeight);
                
                $this->addTextToImage(
                    $image,
                    $cardClassName,
                    $classX,
                    $classY,
                    $fontSize,
                    '#FFFFFF'
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

    private function getQrCodeImage(Guest $guest, ImageManager $manager, float $scale): ?\Intervention\Image\Image
    {
        try {
            if ($guest->qr_code_path) {
                $qrPath = storage_path('app/public/' . $guest->qr_code_path);
                if (file_exists($qrPath)) {
                    $qrSize = (int)(150 * $scale); // Increased QR size for better visibility
                    return $manager->read($qrPath)->resize($qrSize, $qrSize);
                }
            }

            // Generate QR code if not exists
            $qrSize = (int)(150 * $scale); // Increased QR size for better visibility
            $qrCode = QrCode::format('png')
                ->size($qrSize)
                ->margin(5) // Increased margin for better proportion
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
        // Add text shadow effect for better visibility
        $image->text($text, $x + 1, $y + 1, function ($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('#FFFFFF');
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
