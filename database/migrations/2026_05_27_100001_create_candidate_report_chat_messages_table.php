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
        Schema::create('candidate_report_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_id')->constrained('candidate_report_chats')->onDelete('cascade');
            $table->enum('sender', ['user', 'assistant']);
            $table->enum('message_type', ['text', 'audio']);
            $table->text('content'); // stores text message or Whisper transcription
            $table->string('audio_path')->nullable(); // physical file path in storage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_report_chat_messages');
    }
};
