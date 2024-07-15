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
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');

            $table->boolean('auto_backups_enabled')->default(false);
            $table->integer('backup_interval')->default(24)->comment('in hours');
            $table->integer('backup_deletion_interval')->default(7)->comment('in days');
            $table->dateTime('backup_last_run')->nullable();
            $table->dateTime('deletion_last_run')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
