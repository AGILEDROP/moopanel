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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('azure_id')->after('id');
            $table->string('username')->after('azure_id');
            $table->text('azure_token');
            $table->text('azure_access_token');
            $table->text('azure_refresh_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('azure_id');
            $table->dropColumn('username');
            $table->dropColumn('azure_token');
            $table->dropColumn('azure_access_token');
            $table->dropColumn('azure_refresh_token');
        });
    }
};
