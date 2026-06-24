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
        Schema::create('candidate_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->foreignId('recruitment_client_id')->nullable()->constrained('recruitment_clients')->nullOnDelete();
            $table->string('candidate_name');
            $table->string('interviewer_name');
            $table->dateTime('interview_date');
            
            // Campos estruturados
            $table->text('summary')->nullable(); // Resumo Profissional
            $table->text('technical_skills')->nullable(); // Competências Técnicas
            $table->text('behavioral_posture')->nullable(); // Postura Comportamental
            $table->text('strengths')->nullable(); // Pontos Fortes
            $table->text('development_points')->nullable(); // Pontos a Desenvolver
            $table->text('final_opinion')->nullable(); // Parecer Final (texto descritivo)
            
            // Status do parecer
            $table->enum('status', ['recommended', 'recommended_with_reservations', 'not_recommended'])->nullable();
            
            $table->string('audio_path')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_reports');
    }
};
