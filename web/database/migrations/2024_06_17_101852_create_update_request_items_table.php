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
        Schema::create('update_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('update_request_id')->constrained('update_requests')->cascadeOnDelete();
            $table->boolean('status')->nullable()->comment('null=in_progress, true=all updates successful, false=one or more updates failed');

            $table->unsignedBigInteger('model_id');
            $table->foreign('model_id')
                ->references('id')
                ->on('updates')
                ->onDelete('cascade');

            $table->string('component')->nullable();
            $table->string('version')->nullable();
            $table->string('release')->nullable();
            $table->string('download')->nullable();
            $table->string('zip_path')->nullable()->comment('Path to the downloaded zip file');
            $table->string('error')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_request_items');
    }
};
