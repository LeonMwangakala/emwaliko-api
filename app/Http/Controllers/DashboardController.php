<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Sales;
use App\Models\Invoice;
use App\Services\NextSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    protected $smsService;

    public function __construct()
    {
        $this->smsService = new NextSmsService();
    }

    /**
     * Get dashboard statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            // Get basic statistics
            $totalEvents = Event::count();
            $totalGuests = Guest::count();
            $totalSales = Sales::sum('total_sale');
            $totalInvoices = Invoice::count();
            
            // Get event status counts
            $activeEvents = Event::whereIn('status', ['initiated', 'inprogress', 'notified', 'scanned'])->count();
            $completedEvents = Event::where('status', 'completed')->count();
            
            // Get sales status counts
            $pendingSales = Sales::where('status', 'Pending')->count();
            $paidSales = Sales::where('status', 'Paid')->count();
            
            // Get SMS balance from Next SMS API
            $smsBalance = $this->getSmsBalance();
            
            $stats = [
                'total_events' => $totalEvents,
                'total_guests' => $totalGuests,
                'total_sales' => $totalSales,
                'total_invoices' => $totalInvoices,
                'active_events' => $activeEvents,
                'completed_events' => $completedEvents,
                'pending_sales' => $pendingSales,
                'paid_sales' => $paidSales,
                'sms_balance' => $smsBalance,
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS balance from Next SMS API
     */
    private function getSmsBalance(): int
    {
        try {
            $baseUrl = config('services.nextsms.base_url');
            $auth = config('services.nextsms.auth');
            
            if (!$baseUrl || !$auth) {
                return 0; // Return 0 if credentials not configured
            }

            $response = Http::withHeaders([
                'Authorization' => $auth,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($baseUrl . '/api/sms/v1/balance');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['balance'] ?? 0;
            }

            return 0;
        } catch (\Exception $e) {
            \Log::error('Failed to fetch SMS balance: ' . $e->getMessage());
            return 0;
        }
    }
}
