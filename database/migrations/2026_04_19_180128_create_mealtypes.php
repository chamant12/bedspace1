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
        Schema::create('mealtypes', function (Blueprint $table) {
            $table->id();
            $table->string('mealType',255);
            $table->timestamps();
        });
        DB::statement("INSERT INTO mealtypes (mealType) VALUES ('Room Only')");
        DB::statement("INSERT INTO mealtypes (mealType) VALUES ('Bed and Breakfast')");
        DB::statement("INSERT INTO mealtypes (mealType) VALUES ('Half Board')");
        DB::statement("INSERT INTO mealtypes (mealType) VALUES ('Full Board')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mealtypes');
    }
};
