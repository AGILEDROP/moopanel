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
        Schema::table('update_request_items', function (Blueprint $table) {
            // First, drop the existing foreign key constraint
            $table->dropForeign(['model_id']);

            // Next, modify the 'model_id' column to be nullable
            $table->unsignedBigInteger('model_id')->nullable()->change();

            // Finally, add a new foreign key constraint with onDelete set to 'SET NULL'
            $table->foreign('model_id')
                ->references('id')
                ->on('updates')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('update_request_items', function (Blueprint $table) {
            // To reverse the migration, first drop the modified foreign key
            $table->dropForeign(['model_id']);

            // Change 'model_id' back to not nullable
            $table->unsignedBigInteger('model_id')->nullable(false)->change();

            // Add the original foreign key constraint back
            $table->foreign('model_id')
                ->references('id')
                ->on('updates')
                ->onDelete('cascade');
        });
    }
};
