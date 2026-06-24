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
        Schema::create('candidate_report_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name')->nullable();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->onDelete('set null');
            $table->foreignId('recruitment_client_id')->nullable()->constrained('recruitment_clients')->onDelete('set null');
            $table->enum('report_type', ['sara', 'player'])->default('sara');
            $table->json('extracted_data')->nullable(); // progressive draft
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_report_chats');
    }
};
