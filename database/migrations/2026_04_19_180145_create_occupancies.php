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
        Schema::create('occupancies', function (Blueprint $table) {
            $table->id();
            $table->integer('occupancy');
            $table->string('occupancy_name',255);
            $table->integer('num_children')->default(0);
            $table->timestamps();
        });
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name) VALUES (1,'Single')");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name) VALUES (2,'Double')");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name) VALUES (3,'Triple')");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name) VALUES (4,'Quadruple')");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name) VALUES (5,'Quintuple')");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (2,'Double+1',1)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (3,'Triple+1',1)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (3,'Triple+2',2)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (4,'Quadruple+1',1)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (4,'Quadruple+2',2)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (4,'Quadruple+3',3)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (5,'Quintuple+1',1)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (5,'Quintuple+2',2)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (5,'Quintuple+3',3)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (5,'Quintuple+4',4)");
        DB::statement("INSERT INTO occupancies (occupancy,occupancy_name,num_children) VALUES (10,'Family',9)");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupancies');
    }
};
