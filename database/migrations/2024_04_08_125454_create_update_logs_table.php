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
        Schema::create('update_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_id')->unique();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('username')->nullable();
            $table->integer('type');
            $table->string('version')->nullable();
            $table->string('targetversion')->nullable();
            $table->string('info')->nullable();
            $table->text('details')->nullable();
            $table->longText('backtrace')->nullable();

            $table->timestamp('timemodified');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_logs');
    }
};
