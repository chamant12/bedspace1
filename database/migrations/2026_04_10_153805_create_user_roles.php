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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name',191);
            $table->integer('user_type_id');
            $table->timestamps();
            $table->unique(['role_name', 'user_type_id']);
        });

        DB::statement("INSERT INTO user_roles (role_name,user_type_id) VALUES ('Admin',1)");
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
