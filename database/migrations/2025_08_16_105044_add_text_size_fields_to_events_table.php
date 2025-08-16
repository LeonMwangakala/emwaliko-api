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
            // Text size fields for card design customization
            $table->integer('name_text_size')->nullable()->after('card_class_text_color')->comment('Font size for guest name text (in pixels)');
            $table->integer('card_class_text_size')->nullable()->after('name_text_size')->comment('Font size for card class text (in pixels)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'name_text_size',
                'card_class_text_size'
            ]);
        });
    }
};
