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
            $table->dropUnique(['email']);
            $table->unique('azure_id');
            $table->unique('username');

            $table->string('email')->nullable()->change();
            $table->string('app_role_id')->nullable(false)->change();

            $table->dropColumn('azure_token');
            $table->dropColumn('azure_access_token');
            $table->dropColumn('azure_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('app_role_id')->nullable()->change();

            $table->unique(['email']);
            $table->dropUnique(['azure_id']);
            $table->dropUnique(['username']);

            $table->text('azure_token')->nullable();
            $table->text('azure_access_token')->nullable();
            $table->text('azure_refresh_token')->nullable();
        });
    }
};
