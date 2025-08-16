<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Sales;
use App\Services\SalesService;
use App\Services\EventStatusService;
use App\Services\GuestCardCleanupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesController extends Controller
{
    /**
     * Get all sales with pagination and search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sales::with(['event', 'package']);
        
        // Search functionality
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('event', function ($eventQuery) use ($search) {
                    $eventQuery->where('event_name', 'like', "%{$search}%");
                })
                ->orWhereHas('package', function ($packageQuery) use ($search) {
                    $packageQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
        
        $perPage = $request->get('per_page', 10);
        $sales = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json($sales);
    }

    /**
     * Get sales information for an event
     */
    public function getEventSales(Event $event): JsonResponse
    {
        $sales = $event->sales()->with('package')->first();
        
        if (!$sales) {
            return response()->json([
                'message' => 'No sales record found for this event',
                'sales' => null
            ], 404);
        }

        return response()->json([
            'sales' => $sales
        ]);
    }

    /**
     * Mark sales as invoiced
     */
    public function markAsInvoiced(Event $event): JsonResponse
    {
        $sales = $event->sales()->first();
        
        if (!$sales) {
            return response()->json([
                'message' => 'No sales record found for this event'
            ], 404);
        }

        // Check if invoice already exists
        if ($sales->invoice()->exists()) {
            return response()->json([
                'message' => 'Invoice already exists for this event'
            ], 422);
        }

        try {
            SalesService::markAsInvoiced($event);
            
            // Create invoice
            $invoice = \App\Models\Invoice::createFromSales($sales);
            
            // Update event status
            EventStatusService::updateEventStatus($event);

            return response()->json([
                'message' => 'Sales marked as invoiced and invoice created successfully',
                'sales' => $event->sales()->with('package')->first(),
                'invoice' => $invoice->load(['sales', 'event', 'sales.package'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark sales as invoiced',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark sales as paid
     */
    public function markAsPaid(Event $event): JsonResponse
    {
        $sales = $event->sales()->first();
        
        if (!$sales) {
            return response()->json([
                'message' => 'No sales record found for this event'
            ], 404);
        }

        SalesService::markAsPaid($event);
        
        // Update event status
        EventStatusService::updateEventStatus($event);

        return response()->json([
            'message' => 'Sales marked as paid successfully',
            'sales' => $event->sales()->with('package')->first()
        ]);
    }

    /**
     * Get sales summary for an event
     */
    public function getSalesSummary(Event $event): JsonResponse
    {
        $summary = SalesService::getEventSalesSummary($event);

        return response()->json([
            'summary' => $summary
        ]);
    }

    /**
     * Check if event can be marked as completed
     */
    public function canMarkAsCompleted(Event $event): JsonResponse
    {
        $canComplete = SalesService::canMarkEventAsCompleted($event);
        
        // Get cleanup statistics
        $cleanupStats = GuestCardCleanupService::getEventCleanupStats($event);

        return response()->json([
            'can_complete' => $canComplete,
            'reason' => $canComplete ? 'Event can be completed' : 'Event must be invoiced before completion',
            'cleanup_stats' => $cleanupStats
        ]);
    }

    /**
     * Manually mark event as completed (only if invoiced)
     */
    public function markEventAsCompleted(Event $event): JsonResponse
    {
        if (!SalesService::canMarkEventAsCompleted($event)) {
            return response()->json([
                'message' => 'Event must be invoiced before it can be marked as completed',
                'error' => 'EVENT_COMPLETION_RESTRICTED'
            ], 422);
        }

        $success = EventStatusService::markEventAsCompleted($event);

        if ($success) {
            // Get cleanup statistics after completion
            $cleanupStats = GuestCardCleanupService::getEventCleanupStats($event);
            
            return response()->json([
                'message' => 'Event marked as completed successfully. Guest cards have been cleaned up.',
                'event' => $event->load(['customer', 'eventType', 'cardType', 'package']),
                'cleanup_stats' => $cleanupStats
            ]);
        }

        return response()->json([
            'message' => 'Failed to mark event as completed',
            'error' => 'COMPLETION_FAILED'
        ], 500);
    }
}
