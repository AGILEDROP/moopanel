<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('update_logs', function (Blueprint $table) {
            $table->dropUnique(['operation_id']);

            $table->unique(['operation_id', 'instance_id'], 'update_logs_operation_id_instance_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('update_logs', function (Blueprint $table) {
            $table->dropUnique('update_logs_operation_id_instance_id_unique');

            $table->unique('operation_id');
        });
    }
};
