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
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Colombo District',1)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Gampaha District',1)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Kalutara District',1)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Kandy District',8)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Matale District',8)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Nuwara Eliya District',8)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Galle District',2)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Matara District',2)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Hambantota District',2)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Jaffna District',5)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Kilinochchi District',5)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Mannar District',5)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Vavuniya District',5)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Mullaitivu District',5)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Batticaloa District',4)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Ampara District',4)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Trincomalee District',4)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Kurunegala District',3)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Puttalam District',3)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Anuradhapura District',7)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Polonnaruwa District',7)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Badulla District',9)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Moneragala District',9)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Ratnapura District',6)");
        DB::statement("INSERT INTO districts (district_name,province_id) VALUES ('Kegalle District',6)");
    }
    
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
