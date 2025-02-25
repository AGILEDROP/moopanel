<?php

use App\Enums\Status;
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
        if (DB::table('instances')->count() > 0) {
            DB::table('instances')->truncate();
        }

        Schema::table('instances', function (Blueprint $table) {
            $table->dropForeign(['university_member_id']);
            $table->dropColumn('university_member_id');
        });

        Schema::table('instances', function (Blueprint $table) {
            $table->foreignId('university_member_id')
                ->nullable()
                ->after('id')
                ->constrained();
            $table->unique('url');
            $table->renameColumn('name', 'site_name');
            $table->renameColumn('img_path', 'logo');

            $table->text('api_key')->after('url');
            $table->date('key_expiration_date')->nullable()->after('api_key');
            $table->string('theme')->nullable()->after('logo');
            $table->string('version')->nullable()->after('theme');
            $table->string('status')->default(Status::Disconnected->value)->after('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropForeign(['university_member_id']);
            $table->dropColumn('university_member_id');
        });

        Schema::table('instances', function (Blueprint $table) {
            $table->foreignId('university_member_id')
                ->after('id')
                ->constrained();
            $table->dropUnique(['url']);
            $table->renameColumn('site_name', 'name');
            $table->renameColumn('logo', 'img_path');

            $table->dropColumn('api_key');
            $table->dropColumn('key_expiration_date');
            $table->dropColumn('theme');
            $table->dropColumn('version');
            $table->dropColumn('status');
        });
    }
};
