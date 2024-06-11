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
        Schema::create('delta_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('first_instance_id');
            $table->unsignedBigInteger('second_instance_id');

            $table->foreign('first_instance_id')
                ->references('id')
                ->on('instances')
                ->onDelete('cascade');

            $table->foreign('second_instance_id')
                ->references('id')
                ->on('instances')
                ->onDelete('cascade');

            $table->boolean('first_instance_config_received')->default(false);
            $table->boolean('second_instance_config_received')->default(false);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delta_reports');
    }
};
