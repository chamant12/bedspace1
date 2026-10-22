<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Booking engine (FR-05 to FR-09, FR-14). Nothing here is ever deleted:
     * reservations change status, room assignments are released.
     */
    public function up(): void
    {
        // One row per real-world guest; bookings are matched to it by email, phone or name + address
        Schema::create('guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete(); // optional guest account
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('country', 100)->nullable();
            // Normalised keys used for matching returning guests
            $table->string('email_key', 255)->nullable()->index();
            $table->string('phone_key', 50)->nullable()->index();
            $table->string('name_address_key', 64)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('guest_profile_id')->constrained('guest_profiles')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete(); // guest account, if any
            $table->string('status', 20)->index();        // App\Enums\ReservationStatus
            $table->string('payment_method', 20);         // pay_at_hotel | online
            $table->string('payment_status', 20)->default('unpaid'); // unpaid | partially_paid | paid (Phase 4)
            $table->string('source', 20)->default('web'); // web | owner
            $table->date('check_in')->index();
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');

            // Guest details as entered for this booking
            $table->string('guest_name', 255);
            $table->string('guest_email', 255);
            $table->string('guest_phone', 50);
            $table->string('guest_address', 500)->nullable();
            $table->string('guest_country', 100)->nullable();
            $table->text('special_requests')->nullable();

            // Money, in the booking currency, plus the owner's exchange-rate snapshot
            $table->foreignId('currency_type_id')->constrained('currency_types')->restrictOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('exchange_rate', 12, 4); // LKR per 1 unit of the booking currency
            $table->decimal('total_lkr', 14, 2);

            $table->timestamp('expires_at')->nullable(); // pending (unpaid online) bookings only
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'check_in']);
        });

        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->restrictOnDelete();
            $table->foreignId('roomType_id')->constrained('roomtypes')->restrictOnDelete();
            $table->foreignId('occupancy_id')->constrained('occupancies')->restrictOnDelete();
            $table->foreignId('mealType_id')->constrained('mealtypes')->restrictOnDelete();
            $table->unsignedSmallInteger('rooms');
            $table->json('nightly_rates');                 // {"Y-m-d": price per room}
            $table->decimal('subtotal', 12, 2);            // all nights x rooms
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        // Promotion discounts applied to a line (early bird, last minute, ...)
        Schema::create('reservation_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_room_id')->constrained('reservation_rooms')->restrictOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        // Audit trail of every status change
        Schema::create('reservation_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Room numbers occupied by a reservation (assigned at check-in, released at check-out)
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_room_id')->constrained('reservation_rooms')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
        Schema::dropIfExists('reservation_status_history');
        Schema::dropIfExists('reservation_discounts');
        Schema::dropIfExists('reservation_rooms');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('guest_profiles');
    }
};
