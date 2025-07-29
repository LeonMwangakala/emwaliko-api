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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('scan_count')->default(0);
            $table->foreignId('scanned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('scanned_date')->nullable();
            $table->enum('status', ['scanned', 'not_scanned'])->default('not_scanned');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
