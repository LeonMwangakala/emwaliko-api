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

            // Add QR code FIRST (before any text elements)
            if ($cardType->show_qr_code) {
                try {
                    $qrSize = 150;
                    
                    // Generate QR code directly in memory
                    $qrContent = $guest->invite_code;
                    $qrCodeData = QrCode::format('png')
                        ->size($qrSize)
                        ->margin(3)
                        ->generate($qrContent);
                    
                    // Test: Add text at the same position first
                    $targetWidth = $image->width();
                    $targetHeight = $image->height();
                    $qrX = (int)((($event->qr_position_x ?? 80) / 100) * $targetWidth);
                    $qrY = (int)((($event->qr_position_y ?? 70) / 100) * $targetHeight);
                    
                    // Add test text
                    $image->text('QR TEST', $qrX, $qrY, function ($font) {
                        $font->size(50);
                        $font->color('#FF0000'); // Red text
                        $font->align('left');
                        $font->valign('top');
                    });
                    
                    // Try simple place() method with the QR code
                    $qrBase64 = base64_encode($qrCodeData);
                    $qrDataUri = "data:image/png;base64," . $qrBase64;
                    $qrImage = $manager->read($qrDataUri);
                    
                    if ($qrImage) {
                        // Use the same position as the test text
                        $image->place($qrImage, $qrX, $qrY);
                        Log::info("QR code placed using place() method at position", ["x" => $qrX, "y" => $qrY]);
                    }
                    
                } catch (\Exception $e) {
                    Log::error("QR code generation failed", ["error" => $e->getMessage()]);
                }
            }

            // Add guest name if enabled
            if ($cardType->show_guest_name) {
                // Use event-specific text size and color, fallback to defaults
                $fontSize = (int)(($event->name_text_size ?? 98) * $scale / 3.5); // Scale down for the service
                $textColor = $event->name_text_color ?? '#000000';
                
                // Use event-specific positions, fallback to defaults
                $nameX = (int)((($event->name_position_x ?? 50) / 100) * $targetWidth);
                $nameY = (int)((($event->name_position_y ?? 30) / 100) * $targetHeight);
                
                $this->addTextToImage(
                    $image,
                    $guestName,
                    $nameX,
                    $nameY,
                    $fontSize,
                    $textColor
                );
            }


            // Add card class if enabled
            if ($cardType->show_card_class && $cardClassName) {
                // Use event-specific text size and color, fallback to defaults
                $fontSize = (int)(($event->card_class_text_size ?? 60) * $scale / 3.5); // Scale down for the service
                $textColor = $event->card_class_text_color ?? '#333333';
                
                // Use event-specific positions, fallback to defaults
                $classX = (int)((($event->card_class_position_x ?? 20) / 100) * $targetWidth);
                $classY = (int)((($event->card_class_position_y ?? 90) / 100) * $targetHeight);
                
                $this->addTextToImage(
                    $image,
                    $cardClassName,
                    $classX,
                    $classY,
                    $fontSize,
                    $textColor
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

    public function generateGuestCardFromCanvas(Guest $guest, Event $event, string $canvasImageData): string
    {
        try {
            // Decode base64 image data
            $imageData = $canvasImageData;
            if (strpos($imageData, 'data:image/png;base64,') === 0) {
                $imageData = substr($imageData, 22); // Remove data URI prefix
            }

            $imageData = base64_decode($imageData);
            if ($imageData === false) {
                throw new \Exception('Invalid base64 image data');
            }

            // Generate unique filename for this guest card
            $filename = 'guest_cards/' . $guest->invite_code . '_' . time() . '.png';
            $fullPath = storage_path('app/public/' . $filename);

            // Ensure directory exists
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save the canvas-generated image
            file_put_contents($fullPath, $imageData);

            // Return the public URL for WhatsApp
            $publicUrl = url('storage/' . $filename);

            Log::info('Canvas-generated guest card saved', [
                'guest_id' => $guest->id,
                'event_id' => $event->id,
                'filename' => $filename,
                'url' => $publicUrl
            ]);

            return $publicUrl;

        } catch (\Exception $e) {
            Log::error('Failed to generate guest card from canvas', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
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

    private function addQrCodeToCanvas($image, $qrCodeData, int $qrSize, Event $event, $manager)
    {
        try {
            // Create a temporary file for the QR code
            $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
            file_put_contents($tempQrPath, $qrCodeData);
            
            // Load QR code image
            $qrImage = imagecreatefrompng($tempQrPath);
            if (!$qrImage) {
                throw new \Exception('Failed to create QR code image');
            }
            
            // Get QR code dimensions
            $qrWidth = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);
            
            // Calculate position based on event settings
            $targetWidth = $image->width();
            $targetHeight = $image->height();
            
            $qrX = (int)((($event->qr_position_x ?? 80) / 100) * $targetWidth);
            $qrY = (int)((($event->qr_position_y ?? 70) / 100) * $targetHeight);
            
            // Ensure QR code stays within bounds
            $maxX = $targetWidth - $qrSize;
            $maxY = $targetHeight - $qrSize;
            $qrX = max(0, min($qrX, $maxX));
            $qrY = max(0, min($qrY, $maxY));
            
            Log::info("Canvas QR code positioning", [
                "event_qr_x" => $event->qr_position_x,
                "event_qr_y" => $event->qr_position_y,
                "calculated_x" => $qrX,
                "calculated_y" => $qrY,
                "qr_size" => $qrSize,
                "target_width" => $targetWidth,
                "target_height" => $targetHeight
            ]);
            
            // Save the current image to a temporary file
            $tempImagePath = tempnam(sys_get_temp_dir(), 'card_');
            $image->save($tempImagePath);
            
            // Load the main image as GD resource
            $gdImage = imagecreatefrompng($tempImagePath);
            if (!$gdImage) {
                throw new \Exception('Failed to create main image GD resource');
            }
            
            // Copy QR code onto the main image
            imagecopy($gdImage, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight);
            
            // Save the modified image back
            imagepng($gdImage, $tempImagePath);
            
            // Clean up
            imagedestroy($qrImage);
            imagedestroy($gdImage);
            unlink($tempQrPath);
            
            // Reload the modified image into Intervention Image
            $modifiedImage = $manager->read($tempImagePath);
            
            // Clean up temp file
            unlink($tempImagePath);
            
            return $modifiedImage;
            
        } catch (\Exception $e) {
            Log::error("Canvas QR code placement failed", ["error" => $e->getMessage()]);
            // Return original image if QR code placement fails
            return $image;
        }
    }
}
