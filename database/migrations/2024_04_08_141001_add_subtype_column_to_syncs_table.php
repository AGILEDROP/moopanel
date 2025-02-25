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
        Schema::table('syncs', function (Blueprint $table) {
            $table->renameColumn('syncable_type', 'type');
            $table->string('subtype')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syncs', function (Blueprint $table) {
            $table->renameColumn('type', 'syncable_type');
            $table->dropColumn('subtype');
        });
    }
};
