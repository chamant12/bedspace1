<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments (FR-10) and platform commission.
     *
     * Commission is earned per completed payment at the rate fixed on the
     * reservation when it was booked. Every payment stores the owner's
     * exchange-rate snapshot so income reports can be totalled in LKR.
     */
    public function up(): void
    {
        // Admin-set commission per property; null = platform default (config bedspace.default_commission_rate)
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('is_active');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('total_lkr'); // fixed at booking time
            $table->decimal('amount_paid', 12, 2)->default(0)->after('commission_rate');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->restrictOnDelete();
            $table->string('method', 20);        // App\Enums\PaymentChannel
            $table->string('collected_by', 10);  // platform | property
            $table->string('status', 20)->index(); // pending | completed | failed
            $table->string('idempotency_key', 64)->unique(); // duplicate submissions are rejected

            $table->foreignId('currency_type_id')->constrained('currency_types')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('exchange_rate', 12, 4)->nullable(); // owner's LKR rate when completed
            $table->decimal('amount_lkr', 14, 2)->nullable();
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->decimal('commission_lkr', 14, 2)->nullable();

            $table->string('gateway', 30)->nullable();           // dummy | sampath (online only)
            $table->string('gateway_reference', 100)->nullable(); // gateway transaction id / receipt no.
            $table->string('reference', 100)->nullable();         // receipt / slip number for offline payments
            $table->string('notes', 500)->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'amount_paid']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
