<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete all exiting records to insert the foreign key!
        DB::table('instances')->truncate();

        Schema::table('instances', function (Blueprint $table) {
            $table->foreignId('university_member_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropForeign(['university_member_id']);
            $table->dropColumn('university_member_id');
        });
    }
};
