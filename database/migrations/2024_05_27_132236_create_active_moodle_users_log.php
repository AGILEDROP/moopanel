<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_moodle_users_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            $table->integer('active_num');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_moodle_users_log');
    }
};
