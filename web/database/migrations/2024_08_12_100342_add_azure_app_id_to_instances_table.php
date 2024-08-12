<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->string('azure_app_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn('azure_app_id');
        });
    }
};
