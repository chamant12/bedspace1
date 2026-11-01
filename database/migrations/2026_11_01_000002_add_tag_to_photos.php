<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a photo shows: entrance, lobby, pool... for property photos;
     * bed, bathroom, room amenities... for room photos.
     * The keys are listed in config/bedspace.php (photo_tags); null = not tagged yet.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('tag', 30)->nullable()->after('type_id');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('tag');
        });
    }
};
