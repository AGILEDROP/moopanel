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
        Schema::dropIfExists('enterprise_application_instance');
        Schema::dropIfExists('enterprise_applications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('enterprise_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('enterprise_application_instance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBiginteger('enterprise_application_id');
            $table->unsignedBiginteger('instance_id');

            $table->foreign('enterprise_application_id')
                ->references('id')
                ->on('enterprise_applications')
                ->onDelete('cascade');
            $table->foreign('instance_id')
                ->references('id')
                ->on('instances')
                ->onDelete('cascade');
        });
    }
};
