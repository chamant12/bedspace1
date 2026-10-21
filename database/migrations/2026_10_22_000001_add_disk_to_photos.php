<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which storage disk each photo lives on, so photos can be
     * moved to a bucket gradually without breaking existing URLs.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('disk', 50)->default('public')->after('id');
            $table->boolean('has_thumbnail')->default(false)->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['disk', 'has_thumbnail']);
        });
    }
};
