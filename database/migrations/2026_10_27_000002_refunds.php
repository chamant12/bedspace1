<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refunds are payment rows with kind = refund and negative amounts
     * (amount, LKR amount and commission), so income, commission, dues and
     * payout totals net them out automatically.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('kind', 10)->default('payment')->after('reservation_id'); // payment | refund
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('amount_refunded', 12, 2)->default(0)->after('refund_due');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('amount_refunded');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
