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
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('user_type',255);
            $table->timestamps();
        });

        DB::statement("INSERT INTO user_types (user_type) VALUES ('Admin User')");
        DB::statement("INSERT INTO user_types (user_type) VALUES ('Property Owner')");
        DB::statement("INSERT INTO user_types (user_type) VALUES ('Customer')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_types');
    }
};
