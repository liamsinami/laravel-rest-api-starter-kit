<?php

declare(strict_types=1);

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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE VARCHAR(255) USING subject_id::text;');
            DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE VARCHAR(255) USING causer_id::text;');
        } else {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->string('subject_id', 255)->nullable()->change();
                $table->string('causer_id', 255)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE UUID USING subject_id::uuid;');
            DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE UUID USING causer_id::uuid;');
        } else {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->uuid('subject_id')->nullable()->change();
                $table->uuid('causer_id')->nullable()->change();
            });
        }
    }
};
