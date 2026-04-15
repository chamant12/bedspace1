<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('city_id');
            $table->string('contact_name', 255);
            $table->string('contact_number', 255);
            $table->string('contact_email', 255);
            $table->string('address', 255);
            $table->string('location', 255)->nullable();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('district_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            //
        });
    }
};
