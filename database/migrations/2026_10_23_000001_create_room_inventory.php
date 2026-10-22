<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventory per property x room type:
     * - property_room_types.total_rooms: how many rooms of the (owner-level)
     *   room type the property has
     * - room_inventory: per date, rooms closed by the owner (reserved /
     *   locked / closed) and rooms booked by guests
     * Available = total - closed - booked (0 on dates closed in the rates calendar).
     *
     * Room numbers stay in `rooms` (now linked to a property) and are not tied
     * to the counts.
     */
    public function up(): void
    {
        Schema::create('property_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('roomType_id')->constrained('roomtypes')->restrictOnDelete();
            $table->unsignedInteger('total_rooms')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'roomType_id']);
        });

        Schema::create('room_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('roomType_id')->constrained('roomtypes')->restrictOnDelete();
            $table->date('inventory_date');
            $table->unsignedInteger('closed_rooms')->default(0);
            $table->unsignedInteger('booked_rooms')->default(0);
            $table->timestamps();

            $table->unique(['property_id', 'roomType_id', 'inventory_date'], 'room_inventory_slot');
        });

        // Existing room types: their room count becomes the total at the property they were created in
        $roomCounts = DB::table('rooms')->selectRaw('roomType_id, COUNT(*) as total')->groupBy('roomType_id')->pluck('total', 'roomType_id');
        DB::table('roomtypes')->orderBy('id')->each(function ($roomType) use ($roomCounts) {
            DB::table('property_room_types')->insert([
                'property_id' => $roomType->property_id,
                'roomType_id' => $roomType->id,
                'total_rooms' => $roomCounts[$roomType->id] ?? 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Room numbers belong to a property
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->after('id');
            $table->index(['property_id', 'roomType_id']);
        });
        DB::table('rooms')->update([
            'property_id' => DB::raw('(SELECT property_id FROM roomtypes WHERE roomtypes.id = rooms.roomType_id)'),
        ]);
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('rooms', function (Blueprint $table) {
                $table->foreign('property_id')->references('id')->on('properties')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropForeign(['property_id']);
            });
        }
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['property_id', 'roomType_id']);
            $table->dropColumn('property_id');
        });

        Schema::dropIfExists('room_inventory');
        Schema::dropIfExists('property_room_types');
    }
};
