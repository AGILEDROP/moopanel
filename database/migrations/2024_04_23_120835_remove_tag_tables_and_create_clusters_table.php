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
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');

        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('img_path')->nullable();
            $table->foreignId('master_id')->nullable()->constrained('instances');
            $table->boolean('default')->default(false);
            $table->timestamps();
        });

        DB::table('clusters')->insert([
            'name' => __('Default'),
            'img_path' => null,
            'default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clusters');

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');

            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }
};
