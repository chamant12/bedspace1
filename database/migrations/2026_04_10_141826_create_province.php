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
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('province_name',255);
            $table->timestamps();
        });

        DB::statement("INSERT INTO provinces (province_name) VALUES ('Western Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('Southern Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('North Western Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('Estern Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('Nothern Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('Sabaragamuwa Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('North Central Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('Central Province')");
        DB::statement("INSERT INTO provinces (province_name) VALUES ('Uva Province')");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
