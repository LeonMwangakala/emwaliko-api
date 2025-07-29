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
        Schema::table('guests', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['card_type_id']);
            
            // Rename the column
            $table->renameColumn('card_type_id', 'card_class_id');
            
            // Add the new foreign key constraint
            $table->foreign('card_class_id')->references('id')->on('card_classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['card_class_id']);
            
            // Rename the column back
            $table->renameColumn('card_class_id', 'card_type_id');
            
            // Add the original foreign key constraint back
            $table->foreign('card_type_id')->references('id')->on('card_types')->onDelete('cascade');
        });
    }
};
