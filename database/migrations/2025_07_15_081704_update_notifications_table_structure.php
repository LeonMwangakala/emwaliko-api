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
        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns as nullable first
            $table->enum('notification_type', ['SMS', 'WhatsApp'])->nullable()->after('message');
            $table->enum('status', ['Sent', 'Not Sent'])->default('Not Sent')->after('notification_type');
            $table->timestamp('sent_date')->nullable()->after('status');
        });

        // Update existing records to have default values
        DB::table('notifications')->whereNull('notification_type')->update([
            'notification_type' => 'SMS',
            'status' => 'Not Sent'
        ]);

        // NOTE: For PostgreSQL, we cannot change enum to NOT NULL easily. Enforce at application level if needed.
        // Schema::table('notifications', function (Blueprint $table) {
        //     $table->enum('notification_type', ['SMS', 'WhatsApp'])->nullable(false)->change();
        // });

        // Drop old columns
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_notifications', 'sms_notifications', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Restore original columns
            $table->boolean('whatsapp_notifications')->default(false);
            $table->boolean('sms_notifications')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->dropColumn(['sms_reference']);
        });

        // Drop new columns
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['notification_type', 'status', 'sent_date']);
        });
    }
};
