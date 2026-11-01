<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No more currency conversion: money is totalled per currency (LKR
     * amounts in LKR, USD amounts in USD) and never combined.
     *
     * - The LKR snapshot columns (exchange_rate, total_lkr, amount_lkr,
     *   commission_lkr) are no longer written; they stay for old rows.
     * - Commission settlements and hotel payouts are recorded per currency.
     * - currency_rates (owners' exchange rates) is kept but no longer used.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('exchange_rate', 12, 4)->nullable()->change();
            $table->decimal('total_lkr', 14, 2)->nullable()->change();
        });

        foreach (['commission_settlements', 'hotel_payouts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('currency_type_id')->nullable()->after('property_id')->constrained('currency_types')->restrictOnDelete();
                $table->decimal('amount', 14, 2)->default(0)->after('currency_type_id');
                $table->decimal('amount_lkr', 14, 2)->nullable()->change();
            });
        }

        // Existing records: the currency of their payments (LKR snapshot if they mixed currencies)
        $lkrId = DB::table('currency_types')->where('code', 'LKR')->value('id');
        $backfill = function (string $tableName, string $foreignKey, string $amountSql) use ($lkrId) {
            foreach (DB::table($tableName)->get() as $row) {
                $sums = DB::table('payments')->where($foreignKey, $row->id)
                    ->groupBy('currency_type_id')
                    ->selectRaw("currency_type_id, SUM($amountSql) as total")
                    ->get();
                $single = $sums->count() === 1 ? $sums->first() : null;
                DB::table($tableName)->where('id', $row->id)->update([
                    'currency_type_id' => $single ? $single->currency_type_id : $lkrId,
                    'amount' => $single ? round((float) $single->total, 2) : (float) $row->amount_lkr,
                ]);
            }
        };
        $backfill('commission_settlements', 'commission_settlement_id', 'commission_amount');
        $backfill('hotel_payouts', 'hotel_payout_id', 'amount - commission_amount');
    }

    public function down(): void
    {
        foreach (['commission_settlements', 'hotel_payouts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['currency_type_id']);
                $table->dropColumn(['currency_type_id', 'amount']);
            });
        }
    }
};
