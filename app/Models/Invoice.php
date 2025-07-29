<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'sales_id',
        'event_id',
        'total_amount',
        'currency',
        'status',
        'invoice_date',
        'due_date',
        'notes',
        'invoice_items',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'invoice_items' => 'array',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the sales record associated with this invoice
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    /**
     * Get the event associated with this invoice
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Generate a unique invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        do {
            $number = 'KRINV' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('invoice_number', $number)->exists());

        return $number;
    }

    /**
     * Create invoice from sales record
     */
    public static function createFromSales(Sales $sales): self
    {
        $event = $sales->event;
        
        // Calculate due date (30 days from invoice date)
        $dueDate = now()->addDays(30);
        
        // Create invoice items from sales data
        $invoiceItems = [
            [
                'description' => $sales->package->name ?? 'Event Package',
                'quantity' => $sales->guest_count,
                'unit_price' => $sales->package->amount ?? 0,
                'total' => $sales->total_sale,
            ]
        ];

        return self::create([
            'invoice_number' => self::generateInvoiceNumber(),
            'sales_id' => $sales->id,
            'event_id' => $sales->event_id,
            'total_amount' => $sales->total_sale,
            'currency' => $sales->package->currency ?? 'TZS',
            'status' => 'Draft',
            'invoice_date' => now(),
            'due_date' => $dueDate,
            'notes' => "Invoice for {$event->event_name}",
            'invoice_items' => $invoiceItems,
        ]);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'Draft' => 'bg-gray-100 text-gray-800',
            'Sent' => 'bg-blue-100 text-blue-800',
            'Paid' => 'bg-green-100 text-green-800',
            'Overdue' => 'bg-red-100 text-red-800',
            'Cancelled' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'Paid' && 
               $this->status !== 'Cancelled' && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): void
    {
        $this->update(['status' => 'Sent']);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => 'Paid']);
        
        // Also update the sales status
        if ($this->sales) {
            $this->sales->update(['status' => 'Paid']);
        }
    }

    /**
     * Mark invoice as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'Cancelled']);
    }
}
