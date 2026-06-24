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
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->onDelete('set null');
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('candidate_phone')->nullable();
            $table->string('file_path');
            $table->string('file_original_name');
            $table->text('ai_analysis')->nullable(); // Análise da IA (JSON)
            $table->integer('adherence_score')->nullable(); // Grau de aderência (0-100)
            $table->text('strengths')->nullable(); // Pontos fortes
            $table->text('attention_points')->nullable(); // Pontos de atenção
            $table->enum('status', ['pending', 'analyzed', 'error'])->default('pending');
            $table->timestamps();

            // Índices para busca
            $table->index('job_id');
            $table->index('candidate_email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};
