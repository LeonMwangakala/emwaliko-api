<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // KRINV + 6 digits
            $table->foreignId('sales_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('TZS');
            $table->enum('status', ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'])->default('Draft');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('invoice_items')->nullable(); // Store line items as JSON
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['invoice_number']);
            $table->index(['sales_id']);
            $table->index(['event_id']);
            $table->index(['status']);
            $table->index(['invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
