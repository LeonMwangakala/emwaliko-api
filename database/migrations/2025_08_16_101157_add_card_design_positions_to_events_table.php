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
        Schema::table('events', function (Blueprint $table) {
            // Card design position fields - each event has its own design
            $table->decimal('name_position_x', 5, 2)->nullable()->after('card_design_path')->comment('X position for guest name (percentage)');
            $table->decimal('name_position_y', 5, 2)->nullable()->after('name_position_x')->comment('Y position for guest name (percentage)');
            $table->decimal('qr_position_x', 5, 2)->nullable()->after('name_position_y')->comment('X position for QR code (percentage)');
            $table->decimal('qr_position_y', 5, 2)->nullable()->after('qr_position_x')->comment('Y position for QR code (percentage)');
            $table->decimal('card_class_position_x', 5, 2)->nullable()->after('qr_position_y')->comment('X position for card class (percentage)');
            $table->decimal('card_class_position_y', 5, 2)->nullable()->after('card_class_position_x')->comment('Y position for card class (percentage)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'name_position_x',
                'name_position_y',
                'qr_position_x',
                'qr_position_y',
                'card_class_position_x',
                'card_class_position_y'
            ]);
        });
    }
};
