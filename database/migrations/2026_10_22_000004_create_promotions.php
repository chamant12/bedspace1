<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promotion campaigns (early bird, last minute, long stay...). Provisioned
     * ahead of the promotions phase; every condition column is optional so a
     * promotion only applies the rules it sets.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('promotion_type', 30); // App\Enums\PromotionType
            $table->string('discount_type', 20);  // percentage | fixed_amount
            $table->decimal('discount_value', 12, 2);
            $table->unsignedBigInteger('currency_type_id')->nullable(); // required for fixed_amount

            // Booking-window conditions (relative to the arrival date)
            $table->unsignedSmallInteger('min_days_before_arrival')->nullable(); // early bird
            $table->unsignedSmallInteger('max_days_before_arrival')->nullable(); // last minute
            $table->unsignedSmallInteger('min_nights')->nullable();              // long stay

            // When the booking is made / when the stay happens
            $table->date('booking_from')->nullable();
            $table->date('booking_to')->nullable();
            $table->date('stay_from')->nullable();
            $table->date('stay_to')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_stackable')->default(false);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['property_id', 'is_active']);
        });

        // Room types a promotion is limited to; no rows = all room types of the property
        Schema::create('promotion_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('roomType_id')->constrained('roomtypes')->cascadeOnDelete();
            $table->unique(['promotion_id', 'roomType_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_room_types');
        Schema::dropIfExists('promotions');
    }
};
