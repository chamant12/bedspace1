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
        Schema::create('base_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('roomType_id');
            $table->integer('rateType_id');
            $table->integer('mealType_id');
            $table->integer('occupancy_id');
            $table->decimal('rate', 12, 4);
            $table->integer('currency_type_id')->default(1);
            $table->date('rate_for');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_rates');
    }
};
