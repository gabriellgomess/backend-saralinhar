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
        Schema::create('culture_fit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('testee_name')->nullable();
            $table->string('testee_email')->nullable();
            $table->string('testee_phone')->nullable();
            $table->string('testee_position')->nullable();
            $table->json('answers')->comment('Respostas (formato: {question_id: rating})');

            // Pontuações por dimensão (0-100)
            $table->integer('score_autonomy')->default(0)->comment('Autonomia');
            $table->integer('score_innovation')->default(0)->comment('Inovação');
            $table->integer('score_hierarchy')->default(0)->comment('Hierarquia/Estrutura');
            $table->integer('score_teamwork')->default(0)->comment('Trabalho em Equipe');
            $table->integer('score_results')->default(0)->comment('Foco em Resultados');
            $table->integer('score_flexibility')->default(0)->comment('Flexibilidade');

            // Análises da IA
            $table->text('ai_analysis')->nullable()->comment('Análise geral pela IA');
            $table->text('cultural_profile')->nullable()->comment('Perfil cultural identificado');
            $table->text('strengths')->nullable()->comment('Pontos fortes culturais');
            $table->text('challenges')->nullable()->comment('Desafios de adaptação');
            $table->text('ideal_environments')->nullable()->comment('Ambientes ideais');
            $table->text('recommendations')->nullable()->comment('Recomendações');

            $table->enum('status', ['pending', 'completed', 'analyzed'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('culture_fit_results');
    }
};
