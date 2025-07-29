<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Sales;
use App\Models\Package;

class SalesService
{
    /**
     * Create or update sales record for an event
     */
    public static function updateEventSales(Event $event): void
    {
        $guestCount = $event->guests()->count();
        $packageId = $event->package_id;
        
        // Find existing sales record or create new one
        $sales = $event->sales()->first();
        
        if (!$sales) {
            $sales = new Sales([
                'event_id' => $event->id,
                'package_id' => $packageId,
                'guest_count' => $guestCount,
                'status' => 'Pending'
            ]);
        } else {
            // Update existing sales record
            $sales->guest_count = $guestCount;
            $sales->package_id = $packageId;
        }
        
        // Calculate total sale
        $package = Package::find($packageId);
        if ($package) {
            $sales->total_sale = $guestCount * $package->amount;
        } else {
            $sales->total_sale = 0.00;
        }
        
        $sales->save();
    }

    /**
     * Update sales when package changes
     */
    public static function updateSalesForPackageChange(Event $event, int $newPackageId): void
    {
        $sales = $event->sales()->first();
        
        if ($sales) {
            $sales->updatePackage($newPackageId);
        } else {
            // Create new sales record if none exists
            self::updateEventSales($event);
        }
    }

    /**
     * Update sales when guest count changes
     */
    public static function updateSalesForGuestCountChange(Event $event): void
    {
        $sales = $event->sales()->first();
        
        if ($sales) {
            $guestCount = $event->guests()->count();
            $sales->updateGuestCount($guestCount);
        } else {
            // Create new sales record if none exists
            self::updateEventSales($event);
        }
    }

    /**
     * Mark sales as invoiced
     */
    public static function markAsInvoiced(Event $event): void
    {
        // Only allow invoicing if event status is 'scanned'
        if ($event->status !== 'scanned') {
            throw new \InvalidArgumentException('Event must be in scanned status to be invoiced');
        }
        
        $sales = $event->sales()->first();
        
        if ($sales) {
            $sales->update(['status' => 'Invoiced']);
        }
    }

    /**
     * Mark sales as paid
     */
    public static function markAsPaid(Event $event): void
    {
        $sales = $event->sales()->first();
        
        if (!$sales) {
            throw new \InvalidArgumentException('No sales record found for this event');
        }
        
        // Only allow marking as paid if sales status is 'Invoiced'
        if ($sales->status !== 'Invoiced') {
            throw new \InvalidArgumentException('Sales must be invoiced before they can be marked as paid');
        }
        
        $sales->update(['status' => 'Paid']);
    }

    /**
     * Check if event can be marked as completed (must be invoiced)
     */
    public static function canMarkEventAsCompleted(Event $event): bool
    {
        $sales = $event->sales()->first();
        
        if (!$sales) {
            return false;
        }
        
        return $sales->status === 'Invoiced' || $sales->status === 'Paid';
    }

    /**
     * Get sales summary for an event
     */
    public static function getEventSalesSummary(Event $event): array
    {
        $sales = $event->sales()->with('package')->first();
        
        if (!$sales) {
            return [
                'guest_count' => 0,
                'package_name' => 'No package',
                'package_amount' => 0,
                'total_sale' => 0,
                'status' => 'No sales record'
            ];
        }
        
        return [
            'guest_count' => $sales->guest_count,
            'package_name' => $sales->package->name ?? 'Unknown',
            'package_amount' => $sales->package->amount ?? 0,
            'total_sale' => $sales->total_sale,
            'status' => $sales->status
        ];
    }
} 