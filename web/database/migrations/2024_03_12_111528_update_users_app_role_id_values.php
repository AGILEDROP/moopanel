<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('app_role_id', '18d14569-c3bd-439b-9a66-3a2aee01d14f')
            ->update([
                'app_role_id' => Role::User->value,
            ]);
        DB::table('users')
            ->where('app_role_id', 'a85a6caa-9627-46c2-a5c7-b10ec75ad876')
            ->update([
                'app_role_id' => Role::MasterAdmin->value,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('app_role_id', Role::User->value)
            ->update([
                'app_role_id' => '18d14569-c3bd-439b-9a66-3a2aee01d14f',
            ]);
        DB::table('users')
            ->where('app_role_id', Role::MasterAdmin->value)
            ->update([
                'app_role_id' => 'a85a6caa-9627-46c2-a5c7-b10ec75ad876',
            ]);
    }
};
