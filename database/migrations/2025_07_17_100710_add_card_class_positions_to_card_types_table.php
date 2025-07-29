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
            $table->decimal('card_class_position_x', 5, 2)->default(20.00)->comment('X position for card class (percentage)');
            $table->decimal('card_class_position_y', 5, 2)->default(90.00)->comment('Y position for card class (percentage)');
            $table->boolean('show_card_class')->default(true)->comment('Whether to show card class on the card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->dropColumn([
                'card_class_position_x',
                'card_class_position_y',
                'show_card_class'
            ]);
        });
    }
};
