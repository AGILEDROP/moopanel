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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('type');
            $table->string('display_name');
            $table->string('component');
            $table->string('version');
            $table->boolean('enabled');
            $table->boolean('is_standard');
            $table->boolean('available_updates');
            $table->string('settings_section')->nullable();
            $table->string('directory')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
