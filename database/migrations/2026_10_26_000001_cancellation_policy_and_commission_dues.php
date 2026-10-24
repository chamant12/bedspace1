<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cancellation policy: each property sets how many days before check-in
     * cancellations stop being refunded (null = BEDSPACE_FREE_CANCELLATION_DAYS).
     * Each reservation keeps the policy it was booked under.
     *
     * Commission dues: commission on payments collected by the hotel is owed
     * to the platform until the admin records a settlement covering it.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancellation_days')->nullable()->after('is_active');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancellation_days')->default(0)->after('commission_rate');
            // Last day a cancellation is refunded; null = non-refundable from the start (booked inside the window)
            $table->date('free_cancellation_until')->nullable()->after('cancellation_days');
            $table->decimal('refund_due', 12, 2)->default(0)->after('cancellation_reason');
            $table->string('refund_status', 20)->default('none')->after('refund_due'); // none | due | refunded
        });

        Schema::create('commission_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->decimal('amount_lkr', 14, 2);
            $table->string('reference', 100)->nullable(); // bank slip / transfer reference
            $table->string('notes', 500)->nullable();
            $table->timestamp('settled_at');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('commission_settlement_id')->nullable()->after('commission_lkr')
                ->constrained('commission_settlements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['commission_settlement_id']);
            $table->dropColumn('commission_settlement_id');
        });
        Schema::dropIfExists('commission_settlements');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['cancellation_days', 'free_cancellation_until', 'refund_due', 'refund_status']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('cancellation_days');
        });
    }
};
