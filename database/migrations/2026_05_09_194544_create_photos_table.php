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
        Schema::create('photos', function (Blueprint $table) {

            $table->id();

            // Storage structure
            $table->string('upload_path'); // e.g. /property_12/
            $table->string('upload_type'); // property | roomType
            $table->unsignedBigInteger('type_id'); // property_id or roomType_id

            // File
            $table->string('file_name');

            // Sorting
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // =========================
            // INDEXES (IMPORTANT)
            // =========================

            // for filtering per entity
            $table->index(['upload_type', 'type_id']);

            // for sorting
            $table->index(['upload_type', 'type_id', 'sort_order']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
