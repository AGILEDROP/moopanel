<?php

use App\Models\Instance;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        $instances = Instance::all();
        foreach ($instances as $instance) {
            $instance->users()->attach(User::pluck('id')->toArray());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_user');
    }
};
