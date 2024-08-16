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
        Schema::table('university_memberables', function (Blueprint $table) {
            $table->string('app_role_assignment_id')->nullable()->after('memberable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('university_memberables', function (Blueprint $table) {
            $table->dropColumn('app_role_assignment_id');
        });
    }
};
