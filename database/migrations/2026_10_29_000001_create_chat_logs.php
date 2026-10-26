<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per chatbot reply: question, answer, tools used and token usage,
     * to review answer quality and track the Claude API spend.
     */
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('visitor', 64)->index();   // hashed session id, not personal data
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('model', 50)->nullable();   // null = not answered by the model (disabled / limit / error)
            $table->string('tools_used', 500)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('outcome', 20)->default('answered'); // answered | disabled | limited | error | refused
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};
