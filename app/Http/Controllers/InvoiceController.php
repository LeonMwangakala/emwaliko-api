<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * Get all invoices with pagination and search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['sales', 'event.customer', 'sales.package']);
        
        // Search functionality
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('event', function ($eventQuery) use ($search) {
                      $eventQuery->where('event_name', 'like', "%{$search}%");
                  })
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->has('status') && !empty($request->get('status'))) {
            $query->where('status', $request->get('status'));
        }
        
        $perPage = $request->get('per_page', 10);
        $invoices = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json($invoices);
    }

    /**
     * Get a specific invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['sales', 'event.customer', 'event.country', 'event.region', 'event.district', 'sales.package']);
        
        return response()->json($invoice);
    }

    /**
     * Create invoice from sales
     */
    public function createFromSales(Sales $sales): JsonResponse
    {
        // Check if invoice already exists for this sales
        if ($sales->invoice()->exists()) {
            return response()->json([
                'message' => 'Invoice already exists for this sales record'
            ], 422);
        }

        // Check if sales is in correct status
        if ($sales->status !== 'Pending') {
            return response()->json([
                'message' => 'Can only create invoice for pending sales'
            ], 422);
        }

        try {
            $invoice = Invoice::createFromSales($sales);
            
            // Update sales status to Invoiced
            $sales->update(['status' => 'Invoiced']);
            
            $invoice->load(['sales', 'event.customer', 'event.country', 'event.region', 'event.district', 'sales.package']);
            
            return response()->json([
                'message' => 'Invoice created successfully',
                'invoice' => $invoice
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Invoice $invoice, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Draft,Sent,Paid,Overdue,Cancelled'
        ]);

        $oldStatus = $invoice->status;
        $newStatus = $request->get('status');

        try {
            switch ($newStatus) {
                case 'Sent':
                    $invoice->markAsSent();
                    break;
                case 'Paid':
                    $invoice->markAsPaid();
                    break;
                case 'Cancelled':
                    $invoice->markAsCancelled();
                    break;
                default:
                    $invoice->update(['status' => $newStatus]);
            }

            $invoice->load(['sales', 'event.customer', 'event.country', 'event.region', 'event.district', 'sales.package']);

            return response()->json([
                'message' => 'Invoice status updated successfully',
                'invoice' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update invoice status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_invoices' => Invoice::count(),
            'total_amount' => Invoice::sum('total_amount'),
            'paid_invoices' => Invoice::where('status', 'Paid')->count(),
            'paid_amount' => Invoice::where('status', 'Paid')->sum('total_amount'),
            'pending_invoices' => Invoice::whereIn('status', ['Draft', 'Sent'])->count(),
            'pending_amount' => Invoice::whereIn('status', ['Draft', 'Sent'])->sum('total_amount'),
            'overdue_invoices' => Invoice::where('status', 'Overdue')->count(),
            'overdue_amount' => Invoice::where('status', 'Overdue')->sum('total_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Download invoice as PDF (placeholder for future implementation)
     */
    public function download(Invoice $invoice): JsonResponse
    {
        // This would generate and return a PDF invoice
        // For now, return invoice data
        $invoice->load(['sales', 'event.customer', 'sales.package']);
        
        return response()->json([
            'message' => 'PDF generation not implemented yet',
            'invoice' => $invoice
        ]);
    }

    /**
     * Delete invoice
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            // Only allow deletion of draft invoices
            if ($invoice->status !== 'Draft') {
                return response()->json([
                    'message' => 'Can only delete draft invoices'
                ], 422);
            }

            $invoice->delete();

            return response()->json([
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
