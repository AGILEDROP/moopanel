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
        Schema::table('instances', function (Blueprint $table) {
            $table->foreignId('cluster_id')->after('id')->constrained('clusters', 'id');
            $table->string('short_name')->after('name');
            $table->renameColumn('logo', 'img_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('instances', function (Blueprint $table) {
                $table->dropForeign(['cluster_id']);
            });
        }

        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn([
                'cluster_id',
                'short_name',
            ]);
        });

        Schema::table('instances', function (Blueprint $table) {
            $table->renameColumn('img_path', 'logo');
        });
    }
};
