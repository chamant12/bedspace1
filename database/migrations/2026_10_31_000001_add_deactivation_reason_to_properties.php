<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Why a property is inactive, so re-enabling an owner only brings back
     * the properties that were switched off with the owner's account.
     * owner | admin | owner_account_disabled
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('deactivated_reason', 30)->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('deactivated_reason');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['deactivated_reason', 'deactivated_at']);
        });
    }
};
