<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->boolean('show_qr_code')->default(true)->after('show_guest_name');
        });

        // Update existing card types to show QR codes
        DB::table('card_types')->update(['show_qr_code' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->dropColumn('show_qr_code');
        });
    }
};
