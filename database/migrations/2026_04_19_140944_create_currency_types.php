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
        Schema::create('currency_types', function (Blueprint $table) {
            $table->id();
            $table->string('currency',255);
            $table->timestamps();
        });

        DB::statement("INSERT INTO currency_types (currency) VALUES ('Rupee')");
        DB::statement("INSERT INTO currency_types (currency) VALUES ('Dollar')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_types');
    }
};
