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
        Schema::table('users', function (Blueprint $table) {
            // Split name into first_name and last_name
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            
            // Add bio field
            $table->text('bio')->nullable()->after('last_name');
            
            // Add user code (KR + 4 digits + 2 letters)
            $table->string('user_code', 8)->unique()->nullable()->after('bio');
            
            // Add address fields
            $table->string('country')->nullable()->after('user_code');
            $table->string('region')->nullable()->after('country');
            $table->string('postal_code')->nullable()->after('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name', 
                'bio',
                'user_code',
                'country',
                'region',
                'postal_code'
            ]);
        });
    }
};
