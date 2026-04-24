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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number',255)->nullable();
            $table->integer('roomType_id');
            $table->enum('booking_status',['vacant','booked','occupied'])->default('vacant');
            $table->enum('housekeeping_status',['dirty','cleaning','ready'])->default('ready');
            $table->enum('reservation_status',['open','closed'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
