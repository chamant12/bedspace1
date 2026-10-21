<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records are never deleted, only deactivated. Tables the app used to
     * delete from get an is_active flag instead.
     */
    private array $tables = [
        'roomtypes',
        'rooms',
        'photos',
        'amenities',
        'property_amenity_xrefs',
        'roomType_amenity_xrefs',
        'property_contacts',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
