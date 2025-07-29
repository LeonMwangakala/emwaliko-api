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
            $table->boolean('show_guest_name')->default(true)->comment('Whether to show guest name on the card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->dropColumn('show_guest_name');
        });
    }
};
