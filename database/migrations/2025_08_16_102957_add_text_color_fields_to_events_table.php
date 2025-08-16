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
            // Text color fields for card design customization
            $table->string('name_text_color', 7)->nullable()->after('card_class_position_y')->comment('Hex color for guest name text (e.g., #000000)');
            $table->string('card_class_text_color', 7)->nullable()->after('name_text_color')->comment('Hex color for card class text (e.g., #333333)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'name_text_color',
                'card_class_text_color'
            ]);
        });
    }
};
