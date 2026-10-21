<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gives currencies an ISO code and symbol so more can be added later.
     * LKR is the base currency: owners' exchange rates are "LKR per 1 unit",
     * and income reports total everything in LKR.
     */
    public function up(): void
    {
        Schema::table('currency_types', function (Blueprint $table) {
            $table->char('code', 3)->nullable()->after('id');
            $table->string('symbol', 10)->nullable()->after('currency');
            $table->boolean('is_base')->default(false)->after('symbol');
            $table->boolean('is_active')->default(true)->after('is_base');
        });

        // Rows seeded by create_currency_types: 1 = Rupee, 2 = Dollar
        DB::table('currency_types')->where('id', 1)->update(['code' => 'LKR', 'currency' => 'Sri Lankan Rupee', 'symbol' => 'Rs.', 'is_base' => true]);
        DB::table('currency_types')->where('id', 2)->update(['code' => 'USD', 'currency' => 'US Dollar', 'symbol' => '$']);

        Schema::table('currency_types', function (Blueprint $table) {
            $table->unique('code');
        });

        // Fast lookup of an owner's latest rate per currency
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->index(['property_owner_id', 'currency_type_id', 'rate_date'], 'currency_rates_owner_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropIndex('currency_rates_owner_lookup');
        });

        Schema::table('currency_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'symbol', 'is_base', 'is_active']);
        });
    }
};
