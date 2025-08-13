<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\CardTypeController;
use App\Http\Controllers\CardClassController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaymentSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WebhookController;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// RSVP routes (public)
Route::get('guests/invite/{inviteCode}', [GuestController::class, 'getGuestByInviteCode'])->name('guest.invite');
Route::post('guests/{inviteCode}/rsvp', [GuestController::class, 'rsvp'])->name('guest.rsvp');

// WhatsApp webhook routes (public)
Route::get('/webhook/whatsapp', [WebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WebhookController::class, 'handle']);
Route::post('/webhook/whatsapp/test', [WebhookController::class, 'testWhatsApp']);
Route::post('/webhook/whatsapp/test-template', [WebhookController::class, 'testTemplateWhatsApp']);
Route::post('/webhook/whatsapp/test-interactive', [WebhookController::class, 'testInteractiveWhatsApp']);
Route::post('/webhook/whatsapp/test-interactive-template', [WebhookController::class, 'testInteractiveTemplateWhatsApp']);
Route::post('/webhook/whatsapp/test-guest-card', [WebhookController::class, 'testGuestCardGeneration']);
Route::post('/webhook/whatsapp/test-guest-wedding-invitation', [WebhookController::class, 'testGuestWeddingInvitation']);
Route::get('/webhook/whatsapp/templates', [WebhookController::class, 'listWhatsAppTemplates']);

// Protected routes (admin only)
Route::middleware('auth:sanctum')->group(function () {
    // Event routes
    Route::apiResource('events', EventController::class);
    Route::get('events/code/{eventCode}', [EventController::class, 'getByEventCode']);
    Route::get('events/{event}/options', [EventController::class, 'getEventOptions']);
    Route::patch('events/{event}/status', [EventController::class, 'updateStatus']);
    Route::get('events/{event}/guests', [GuestController::class, 'getEventGuests']);
    Route::get('events/{event}/guests/all', [GuestController::class, 'getAllEventGuests']);
    Route::post('events/{event}/guests/bulk', [GuestController::class, 'bulkCreate']);
    Route::post('events/{event}/guests/upload', [GuestController::class, 'uploadExcel']);
    Route::post('events/{event}/guests/generate-qr-codes', [GuestController::class, 'generateMissingQrCodes']);
    Route::post('events/{event}/guests/regenerate-qr-codes', [GuestController::class, 'regenerateAllQrCodes']);
    Route::get('events/{event}/notifications', [NotificationController::class, 'getEventNotifications']);
    Route::post('events/{event}/notifications', [NotificationController::class, 'sendNotifications']);
    Route::delete('events/{event}/notifications/{notificationId}', [NotificationController::class, 'destroyEventNotification']);
    Route::get('events/{event}/notifications/available-guests', [NotificationController::class, 'getAvailableGuestsForNotificationType']);
    Route::post('events/{event}/card-design', [EventController::class, 'uploadCardDesign']);
    Route::get('events/{event}/card-design', [EventController::class, 'getCardDesign']);
    Route::delete('events/{event}/card-design', [EventController::class, 'deleteCardDesign']);
    Route::get('events/{event}/scans', [ScanController::class, 'getEventScans']);
    Route::post('events/{event}/scans', [ScanController::class, 'createScan']);
    Route::post('events/{event}/scans/guest-by-qr', [ScanController::class, 'getGuestByQrCode']);
    
    // Sales routes
    Route::get('/sales', [SalesController::class, 'index']);
    Route::get('events/{event}/sales', [SalesController::class, 'getEventSales']);
    Route::get('events/{event}/sales/summary', [SalesController::class, 'getSalesSummary']);
    Route::patch('events/{event}/sales/invoice', [SalesController::class, 'markAsInvoiced']);
    Route::patch('events/{event}/sales/pay', [SalesController::class, 'markAsPaid']);
    Route::get('events/{event}/sales/can-complete', [SalesController::class, 'canMarkAsCompleted']);
    Route::patch('events/{event}/sales/complete', [SalesController::class, 'markEventAsCompleted']);
    
    // Invoice routes
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/statistics', [InvoiceController::class, 'getStatistics']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/sales/{sales}/invoice', [InvoiceController::class, 'createFromSales']);
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    
    // Guest routes
    Route::apiResource('guests', GuestController::class);
    Route::post('guests/{guest}/regenerate-qr-code', [GuestController::class, 'regenerateQrCode']);
    
    // Notification routes
    Route::apiResource('notifications', NotificationController::class);
    Route::patch('/notifications/{notification}/mark-sent', [NotificationController::class, 'markAsSent']);
    Route::patch('/notifications/{notification}/mark-not-sent', [NotificationController::class, 'markAsNotSent']);
    
    // Customer routes
    Route::apiResource('customers', CustomerController::class);
    Route::patch('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus']);
    Route::patch('/customers/{customer}/activate', [CustomerController::class, 'activate']);
    Route::patch('/customers/{customer}/deactivate', [CustomerController::class, 'deactivate']);
    
    // Event Types routes (admin only)
    Route::get('/event-types', [EventTypeController::class, 'index']);
    Route::post('/event-types', [EventTypeController::class, 'store']);
    Route::put('/event-types/{eventType}', [EventTypeController::class, 'update']);
    Route::patch('/event-types/{eventType}/toggle-status', [EventTypeController::class, 'toggleStatus']);
    
    // Card Types routes (admin only)
    Route::get('/card-types', [CardTypeController::class, 'index']);
    Route::post('/card-types', [CardTypeController::class, 'store']);
    Route::put('/card-types/{cardType}', [CardTypeController::class, 'update']);
    Route::patch('/card-types/{cardType}/toggle-status', [CardTypeController::class, 'toggleStatus']);
    
    // Card Classes routes (admin only)
    Route::get('/card-classes', [CardClassController::class, 'index']);
    Route::post('/card-classes', [CardClassController::class, 'store']);
    Route::put('/card-classes/{cardClass}', [CardClassController::class, 'update']);
    Route::patch('/card-classes/{cardClass}/toggle-status', [CardClassController::class, 'toggleStatus']);
    
    // Package routes (admin only)
    Route::get('/packages', [PackageController::class, 'index']);
    Route::post('/packages', [PackageController::class, 'store']);
    Route::put('/packages/{package}', [PackageController::class, 'update']);
    Route::patch('/packages/{package}/toggle-status', [PackageController::class, 'toggleStatus']);
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/picture', [ProfileController::class, 'updateProfilePicture']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    
    // Location routes
    Route::get('/countries', [LocationController::class, 'getCountries']);
    Route::get('/regions/{countryId}', [LocationController::class, 'getRegions']);
    Route::get('/districts/{regionId}', [LocationController::class, 'getDistricts']);
    Route::get('/locations', [LocationController::class, 'getAllLocations']);
    
    // Scanner users route
    Route::get('/users/scanners', [AuthController::class, 'getScannerUsers']);
    
    // User management routes
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/all', [UserController::class, 'getAll']);
    Route::get('/users/statistics', [UserController::class, 'getStatistics']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    
    // Roles routes
    Route::get('/roles', [UserController::class, 'getRoles']);
    
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    
    // Reports
    Route::get('/reports/events', [ReportController::class, 'getEventReports']);
    Route::get('/reports/sales', [ReportController::class, 'getSalesReports']);
    Route::get('/reports/guests', [ReportController::class, 'getGuestReports']);
    Route::get('/reports/financial', [ReportController::class, 'getFinancialReports']);
    Route::get('/reports/notifications', [ReportController::class, 'getNotificationReports']);
    Route::get('/reports/scans', [ReportController::class, 'getScanReports']);
    Route::get('/reports/filter-options', [ReportController::class, 'getFilterOptions']);
});

// VAT Rate settings
Route::get('settings/vat-rate', [SettingController::class, 'getVatRate']);
Route::post('settings/vat-rate', [SettingController::class, 'setVatRate']);

// Payment Settings routes
Route::get('payment-settings', [PaymentSettingController::class, 'show']);
Route::put('payment-settings', [PaymentSettingController::class, 'update']);
