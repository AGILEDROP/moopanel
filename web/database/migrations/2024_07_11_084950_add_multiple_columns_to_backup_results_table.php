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
        Schema::table('backup_results', function (Blueprint $table) {
            $table->foreignId('backup_storage_id')->nullable()->constrained('backup_storages')->onDelete('set null');
            $table->string('filesize')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_results', function (Blueprint $table) {
            $table->dropForeign(['backup_storage_id']);
            $table->dropColumn('backup_storage_id');
            $table->dropColumn('filesize');
        });
    }
};
