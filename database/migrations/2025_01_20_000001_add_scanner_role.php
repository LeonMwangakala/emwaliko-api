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
        // Check if scanner role already exists
        $scannerRole = DB::table('roles')->where('name', 'scanner')->first();
        
        if (!$scannerRole) {
            DB::table('roles')->insert([
                'name' => 'scanner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove scanner role
        DB::table('roles')->where('name', 'scanner')->delete();
    }
};
