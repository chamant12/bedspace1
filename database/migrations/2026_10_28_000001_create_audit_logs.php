<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * General audit trail (FR-14): who changed what, when and from where.
     * Reservation status changes are also kept in reservation_status_history.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action', 50);                 // created | updated | rates.saved | ...
            $table->string('subject_type', 100)->nullable(); // model class basename
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable()->index(); // for owner-scoped viewing
            $table->string('description', 500)->nullable();
            $table->json('changes')->nullable();          // {"field": {"from": ..., "to": ...}}
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
