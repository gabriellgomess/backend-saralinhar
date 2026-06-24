<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_application_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('overall_score', 5, 2)->nullable()
                  ->comment('Score geral normalizado 0-100');
            $table->json('dimension_scores')->nullable()
                  ->comment('Score por dimensão: {slug: {score, classification, mean}}');
            $table->unsignedTinyInteger('quality_index')->nullable()
                  ->comment('Índice de qualidade de resposta 0-100');
            $table->json('flags')->nullable()
                  ->comment('Alertas de qualidade: tempo baixo, repetição excessiva etc.');
            $table->json('report')->nullable()
                  ->comment('Relatório completo gerado: strengths, development_points, recommendations, disclaimer');
            $table->text('ai_narrative')->nullable()
                  ->comment('Texto narrativo gerado pela IA');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index('overall_score');
            $table->index('quality_index');
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
