<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `rooms` now only holds room numbers entered by owners. Placeholder rows
     * without a number (created by the old room type form) are deactivated,
     * and a room number is unique within a property.
     */
    public function up(): void
    {
        DB::table('rooms')->whereNull('room_number')->update(['is_active' => false]);

        Schema::table('rooms', function (Blueprint $table) {
            $table->unique(['property_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['property_id', 'room_number']);
        });
    }
};
