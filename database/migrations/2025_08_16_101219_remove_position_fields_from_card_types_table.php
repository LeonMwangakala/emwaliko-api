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
        Schema::table('card_types', function (Blueprint $table) {
            // Remove position fields - these will be moved to events table
            $table->dropColumn([
                'name_position_x',
                'name_position_y',
                'qr_position_x',
                'qr_position_y',
                'card_class_position_x',
                'card_class_position_y'
            ]);
            
            // Keep the visibility flags - these define what elements are shown
            // show_guest_name, show_qr_code, show_card_class remain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            // Restore position fields
            $table->decimal('name_position_x', 5, 2)->nullable()->comment('X position for guest name (percentage)');
            $table->decimal('name_position_y', 5, 2)->nullable()->comment('Y position for guest name (percentage)');
            $table->decimal('qr_position_x', 5, 2)->nullable()->comment('X position for QR code (percentage)');
            $table->decimal('qr_position_y', 5, 2)->nullable()->comment('Y position for QR code (percentage)');
            $table->decimal('card_class_position_x', 5, 2)->nullable()->comment('X position for card class (percentage)');
            $table->decimal('card_class_position_y', 5, 2)->nullable()->comment('Y position for card class (percentage)');
        });
    }
};
