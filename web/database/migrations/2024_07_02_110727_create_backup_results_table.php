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
        Schema::create('backup_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            $table->integer('moodle_course_id');
            $table->integer('manual_trigger_timestamp')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('url')->nullable();
            $table->boolean('status')->nullable()->comment('null=backup in progress, true=backup success, false=backup failed');
            $table->string('password')->nullable();
            $table->string('message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_results');
    }
};
