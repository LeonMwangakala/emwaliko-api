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
        Schema::table('event_types', function (Blueprint $table) {
            $table->text('sms_template')->nullable()->after('status');
            $table->text('whatsapp_template')->nullable()->after('sms_template');
            $table->text('sms_invitation_template')->nullable()->after('whatsapp_template');
            $table->text('sms_donation_template')->nullable()->after('sms_invitation_template');
            $table->text('whatsapp_invitation_template')->nullable()->after('sms_donation_template');
            $table->text('whatsapp_donation_template')->nullable()->after('whatsapp_invitation_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->dropColumn([
                'sms_template', 
                'whatsapp_template',
                'sms_invitation_template',
                'sms_donation_template',
                'whatsapp_invitation_template',
                'whatsapp_donation_template'
            ]);
        });
    }
}; 