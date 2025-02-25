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
        Schema::create('backup_storages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instance_id')->constrained()->onDelete('cascade');
            $table->boolean('active')->default(false);
            $table->string('name');
            $table->string('storage_key')->comment('Storage disk key(eg s3, local, etc)');
            $table->string('url')->nullable();
            $table->string('key')->nullable();
            $table->string('secret')->nullable();
            $table->string('bucket_name')->nullable();
            $table->string('region')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_storages');
    }
};
