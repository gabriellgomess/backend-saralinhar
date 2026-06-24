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
        Schema::create('candidate_job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_listings')->onDelete('cascade');
            $table->integer('adherence_score')->nullable(); // Grau de aderência à vaga específica (0-100)
            $table->text('strengths')->nullable(); // Pontos fortes para esta vaga específica
            $table->text('attention_points')->nullable(); // Pontos de atenção para esta vaga específica
            $table->text('ai_analysis')->nullable(); // Análise completa em JSON
            $table->enum('status', ['pending', 'analyzed', 'error'])->default('pending');
            $table->timestamps();

            // Índices para otimização
            $table->index(['candidate_id', 'job_id']);
            $table->index('job_id');
            $table->index('status');

            // Garantir que um candidato não se candidate duas vezes na mesma vaga
            $table->unique(['candidate_id', 'job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_job_applications');
    }
};
