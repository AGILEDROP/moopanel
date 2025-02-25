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
        Schema::table('plugins', function (Blueprint $table) {
            $table->dropForeign(['instance_id']);
            $table->dropColumn('instance_id');
            $table->dropColumn('version');
            $table->dropColumn('enabled');
            $table->dropColumn('update_available');

            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plugins', function (Blueprint $table) {
            $table->foreignId('instance_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('version');
            $table->boolean('enabled');
            $table->boolean('update_available');

            $table->dropUnique(['name']);
        });
    }
};
