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
            $table->string('zip_name')->nullable()->after('download');
            $table->unsignedBigInteger('model_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('update_request_items', function (Blueprint $table) {
            $table->dropColumn('zip_name');
            $table->unsignedBigInteger('model_id')->nullable(false)->change();
        });
    }
};
