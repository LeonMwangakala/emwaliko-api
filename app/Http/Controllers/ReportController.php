<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Sales;
use App\Models\Notification;
use App\Models\Scan;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Get event reports with filters
     */
    public function getEventReports(Request $request): JsonResponse
    {
        try {
            $query = Event::with(['customer', 'eventType', 'package', 'country', 'region', 'district']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('event_type_id')) {
                $query->where('event_type_id', $request->event_type_id);
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('date_from')) {
                $query->where('event_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('event_date', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('event_name', 'like', "%{$search}%")
                      ->orWhere('event_code', 'like', "%{$search}%")
                      ->orWhere('event_location', 'like', "%{$search}%");
                });
            }

            $events = $query->orderBy('event_date', 'desc')->paginate($request->get('per_page', 15));

            // Get summary statistics
            $summary = [
                'total_events' => Event::count(),
                'active_events' => Event::whereIn('status', ['initiated', 'inprogress', 'notified', 'scanned'])->count(),
                'completed_events' => Event::where('status', 'completed')->count(),
                'cancelled_events' => Event::where('status', 'cancelled')->count(),
                'total_guests' => Guest::count(),
                'total_revenue' => Sales::sum('total_sale'),
            ];

            return response()->json([
                'data' => $events->items(),
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                ],
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch event reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales reports with filters
     */
    public function getSalesReports(Request $request): JsonResponse
    {
        try {
            $query = Sales::with(['event', 'package']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('package_id')) {
                $query->where('package_id', $request->package_id);
            }

            if ($request->filled('date_from')) {
                $query->whereHas('event', function ($q) use ($request) {
                    $q->where('event_date', '>=', $request->date_from);
                });
            }

            if ($request->filled('date_to')) {
                $query->whereHas('event', function ($q) use ($request) {
                    $q->where('event_date', '<=', $request->date_to);
                });
            }

            $sales = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

            // Get summary statistics
            $summary = [
                'total_sales' => Sales::count(),
                'total_revenue' => Sales::sum('total_sale'),
                'pending_sales' => Sales::where('status', 'Pending')->count(),
                'paid_sales' => Sales::where('status', 'Paid')->count(),
                'total_guests_sold' => Sales::sum('guest_count'),
                'average_sale_amount' => round(Sales::avg('total_sale'), 2),
            ];

            return response()->json([
                'data' => $sales->items(),
                'pagination' => [
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                    'per_page' => $sales->perPage(),
                    'total' => $sales->total(),
                ],
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch sales reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get guest reports with filters
     */
    public function getGuestReports(Request $request): JsonResponse
    {
        try {
            $query = Guest::with(['event', 'cardClass']);

            // Apply filters
            if ($request->filled('event_id')) {
                $query->where('event_id', $request->event_id);
            }

            if ($request->filled('card_class_id')) {
                $query->where('card_class_id', $request->card_class_id);
            }

            if ($request->filled('rsvp_status')) {
                $query->where('rsvp_status', $request->rsvp_status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('invite_code', 'like', "%{$search}%");
                });
            }

            $guests = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

            // Get summary statistics
            $summary = [
                'total_guests' => Guest::count(),
                'rsvp_yes' => Guest::where('rsvp_status', 'Yes')->count(),
                'rsvp_no' => Guest::where('rsvp_status', 'No')->count(),
                'rsvp_maybe' => Guest::where('rsvp_status', 'Maybe')->count(),
                'rsvp_pending' => Guest::where('rsvp_status', 'Pending')->count(),
                'scanned_guests' => Scan::where('status', 'scanned')->count(),
            ];

            return response()->json([
                'data' => $guests->items(),
                'pagination' => [
                    'current_page' => $guests->currentPage(),
                    'last_page' => $guests->lastPage(),
                    'per_page' => $guests->perPage(),
                    'total' => $guests->total(),
                ],
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch guest reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get financial reports
     */
    public function getFinancialReports(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
            $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now()->endOfMonth();

            // Monthly revenue data
            $monthlyRevenue = Sales::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                SUM(total_sale) as revenue,
                COUNT(*) as sales_count
            ')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

            // Package performance
            $packagePerformance = Sales::selectRaw('
                packages.name as package_name,
                SUM(sales.total_sale) as total_revenue,
                COUNT(sales.id) as sales_count,
                AVG(sales.total_sale) as avg_sale
            ')
            ->join('packages', 'sales.package_id', '=', 'packages.id')
            ->whereBetween('sales.created_at', [$dateFrom, $dateTo])
            ->groupBy('packages.id', 'packages.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

            // Customer performance
            $customerPerformance = Event::selectRaw('
                customers.name as customer_name,
                COUNT(events.id) as events_count,
                SUM(sales.total_sale) as total_revenue
            ')
            ->join('customers', 'events.customer_id', '=', 'customers.id')
            ->leftJoin('sales', 'events.id', '=', 'sales.event_id')
            ->whereBetween('events.created_at', [$dateFrom, $dateTo])
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

            // Summary statistics
            $summary = [
                'total_revenue' => Sales::whereBetween('created_at', [$dateFrom, $dateTo])->sum('total_sale'),
                'total_sales' => Sales::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'total_events' => Event::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'total_guests' => Guest::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'pending_revenue' => Sales::where('status', 'Pending')->whereBetween('created_at', [$dateFrom, $dateTo])->sum('total_sale'),
                'paid_revenue' => Sales::where('status', 'Paid')->whereBetween('created_at', [$dateFrom, $dateTo])->sum('total_sale'),
            ];

            return response()->json([
                'monthly_revenue' => $monthlyRevenue,
                'package_performance' => $packagePerformance,
                'customer_performance' => $customerPerformance,
                'summary' => $summary,
                'date_range' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch financial reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification reports
     */
    public function getNotificationReports(Request $request): JsonResponse
    {
        try {
            $query = Notification::with(['guest.event', 'guest']);

            // Apply filters
            if ($request->filled('notification_type')) {
                $query->where('notification_type', $request->notification_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('event_id')) {
                $query->whereHas('guest', function ($q) use ($request) {
                    $q->where('event_id', $request->event_id);
                });
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $notifications = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

            // Get summary statistics
            $summary = [
                'total_notifications' => Notification::count(),
                'sms_notifications' => Notification::where('notification_type', 'SMS')->count(),
                'whatsapp_notifications' => Notification::where('notification_type', 'WhatsApp')->count(),
                'sent_notifications' => Notification::where('status', 'Sent')->count(),
                'not_sent_notifications' => Notification::where('status', 'Not Sent')->count(),
            ];

            return response()->json([
                'data' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch notification reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scan reports
     */
    public function getScanReports(Request $request): JsonResponse
    {
        try {
            $query = Scan::with(['guest.event', 'guest.cardClass', 'scannedBy']);

            // Apply filters
            if ($request->filled('event_id')) {
                $query->whereHas('guest', function ($q) use ($request) {
                    $q->where('event_id', $request->event_id);
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('scanned_by')) {
                $query->where('scanned_by', $request->scanned_by);
            }

            if ($request->filled('date_from')) {
                $query->where('scanned_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('scanned_date', '<=', $request->date_to);
            }

            $scans = $query->orderBy('scanned_date', 'desc')->paginate($request->get('per_page', 15));

            // Get summary statistics
            $summary = [
                'total_scans' => Scan::count(),
                'scanned_guests' => Scan::where('status', 'scanned')->count(),
                'not_scanned_guests' => Scan::where('status', 'not_scanned')->count(),
                'total_quantity_scanned' => Scan::sum('quantity'),
                'total_scan_count' => Scan::sum('scan_count'),
            ];

            return response()->json([
                'data' => $scans->items(),
                'pagination' => [
                    'current_page' => $scans->currentPage(),
                    'last_page' => $scans->lastPage(),
                    'per_page' => $scans->perPage(),
                    'total' => $scans->total(),
                ],
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch scan reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options for reports
     */
    public function getFilterOptions(): JsonResponse
    {
        try {
            $options = [
                'event_statuses' => ['initiated', 'inprogress', 'notified', 'scanned', 'completed', 'cancelled'],
                'sales_statuses' => ['Pending', 'Paid'],
                'notification_types' => ['SMS', 'WhatsApp'],
                'notification_statuses' => ['Sent', 'Not Sent'],
                'scan_statuses' => ['scanned', 'not_scanned'],
                'rsvp_statuses' => ['Yes', 'No', 'Maybe', 'Pending'],
                'customers' => Customer::select('id', 'name')->get(),
                'event_types' => EventType::select('id', 'name')->get(),
                'packages' => Package::select('id', 'name')->get(),
                'events' => Event::select('id', 'event_name', 'event_date')->get(),
            ];

            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch filter options',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 