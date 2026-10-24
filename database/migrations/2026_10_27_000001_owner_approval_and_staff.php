<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - Property owners sign up publicly; an admin approves them before they
     *   can add properties, staff, rates etc. (approved_at)
     * - Hotel staff accounts belong to an owner (owner_id) and are assigned
     *   to some of the owner's properties (property_staff).
     * - can_edit_properties: staff may edit property details (owners always can).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_at');
            $table->unsignedBigInteger('owner_id')->nullable()->after('approved_by_user_id')->index(); // staff only
            $table->boolean('can_edit_properties')->default(false)->after('owner_id');                 // staff only
        });

        if (!DB::table('user_types')->where('id', 4)->exists()) {
            DB::table('user_types')->insert(['id' => 4, 'user_type' => 'Property Staff', 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::create('property_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'user_id']);
        });

        // Accounts that existed before approvals were introduced count as approved
        DB::table('users')->whereNull('approved_at')->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('property_staff');
        DB::table('user_types')->where('id', 4)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'approved_by_user_id', 'owner_id', 'can_edit_properties']);
        });
    }
};
