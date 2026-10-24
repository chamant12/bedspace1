<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payouts: the platform collects online payments and pays each hotel the
     * amount less commission. A payment is "paid out" once linked to a payout.
     */
    public function up(): void
    {
        Schema::create('hotel_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->decimal('amount_lkr', 14, 2);
            $table->string('reference', 100)->nullable(); // bank transfer reference
            $table->string('notes', 500)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('hotel_payout_id')->nullable()->after('commission_settlement_id')
                ->constrained('hotel_payouts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['hotel_payout_id']);
            $table->dropColumn('hotel_payout_id');
        });
        Schema::dropIfExists('hotel_payouts');
    }
};
