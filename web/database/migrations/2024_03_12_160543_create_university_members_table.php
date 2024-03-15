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
        Schema::create('university_members', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('acronym');
            $table->string('name');
            $table->string('sis_base_url');
            $table->string('sis_current_year');
            $table->integer('sis_student_years')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_members');
    }
};
