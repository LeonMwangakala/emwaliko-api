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
                $fontSize = (int)(28 * $scale); // Fixed size: 28px
                
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


            // Add card class if enabled
            if ($cardType->show_card_class && $cardClassName) {
                $fontSize = (int)(20 * $scale); // Fixed size: 20px (much smaller)
                
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

            // Add QR code as TOP LAYER - after all other elements
            if ($cardType->show_qr_code) {
                try {
                    $qrSize = 150; // Reduced size for better proportion
                    
                    // Generate QR code
                    $qrContent = $guest->invite_code;
                    Log::info("QR Content Generated (Top Layer)", ["content" => $qrContent]);
                    
                    $qrCode = QrCode::format('png')
                        ->size($qrSize)
                        ->margin(3)
                        ->generate($qrContent);
                    
                    // Convert to base64 data URI
                    $qrBase64 = base64_encode($qrCode);
                    $qrDataUri = "data:image/png;base64," . $qrBase64;
                    Log::info("QR base64 created (Top Layer)", ["length" => strlen($qrBase64)]);
                    
                    $qrImage = $manager->read($qrDataUri);
                    
                    if ($qrImage) {
                        // Back to working top-left position
                        $qrX = 50; // Back to top-left where it was working
                        $qrY = 50; // Back to top-left where it was working
                        
                        Log::info("Placing QR on TOP LAYER", ["qrX" => $qrX, "qrY" => $qrY, "qrSize" => $qrSize]);
                        
                        // Place QR as the final layer
                        $image->place($qrImage, $qrX, $qrY);
                        
                        Log::info("QR placed successfully on top layer");
                    }
                } catch (\Exception $e) {
                    Log::error("QR Top Layer failed", ["error" => $e->getMessage()]);
                }
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
                    $qrSize = (int)(200 * $scale); // Compact QR size
                    return $manager->read($qrPath)->resize($qrSize, $qrSize);
                }
            }

            // Generate QR code if not exists
            $qrSize = (int)(200 * $scale); // Compact QR size
            $qrCode = QrCode::format('png')
                ->size($qrSize)
                ->margin(10) // Larger white margin for better visibility
                ->backgroundColor(255, 255, 255) // White background for better contrast
                ->color(0, 0, 0) // Black QR code for maximum contrast
                ->errorCorrection('H') // High error correction for better scanning
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
            $font->file("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf");
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });

        // Add main text
        $image->text($text, $x, $y, function ($font) use ($fontSize, $color) {
            $font->size($fontSize);
            $font->file("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf");
            $font->color($color);
            $font->align('center');
            $font->valign('middle');
        });
    }
}
