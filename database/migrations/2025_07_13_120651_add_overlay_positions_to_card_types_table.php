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
            $table->decimal('name_position_x', 5, 2)->default(50.00)->comment('X position for guest name (percentage)');
            $table->decimal('name_position_y', 5, 2)->default(30.00)->comment('Y position for guest name (percentage)');
            $table->decimal('qr_position_x', 5, 2)->default(80.00)->comment('X position for QR code (percentage)');
            $table->decimal('qr_position_y', 5, 2)->default(70.00)->comment('Y position for QR code (percentage)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->dropColumn([
                'name_position_x',
                'name_position_y', 
                'qr_position_x',
                'qr_position_y'
            ]);
        });
    }
};
