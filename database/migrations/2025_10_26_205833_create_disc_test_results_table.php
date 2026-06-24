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
        Schema::create('disc_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('answers')->comment('Respostas do usuário (formato: {question_id: {most: "D", least: "S"}})');
            $table->integer('score_d')->default(0)->comment('Pontuação Dominância');
            $table->integer('score_i')->default(0)->comment('Pontuação Influência');
            $table->integer('score_s')->default(0)->comment('Pontuação Estabilidade');
            $table->integer('score_c')->default(0)->comment('Pontuação Conformidade');
            $table->string('primary_profile', 1)->comment('Perfil primário (D, I, S ou C)');
            $table->string('secondary_profile', 1)->nullable()->comment('Perfil secundário');
            $table->text('ai_analysis')->nullable()->comment('Análise detalhada gerada pela IA');
            $table->text('strengths')->nullable()->comment('Pontos fortes identificados pela IA');
            $table->text('development_areas')->nullable()->comment('Áreas de desenvolvimento');
            $table->text('ideal_roles')->nullable()->comment('Funções ideais');
            $table->text('work_style')->nullable()->comment('Estilo de trabalho');
            $table->enum('status', ['pending', 'completed', 'analyzed'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('primary_profile');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disc_test_results');
    }
};
