<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_option_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Opção escolhida (SJT / single_choice)');
            $table->unsignedTinyInteger('numeric_answer')->nullable()
                  ->comment('Valor numérico da resposta (Likert, eNPS)');
            $table->text('text_answer')->nullable()->comment('Resposta dissertativa (open_text)');
            $table->json('ranking_json')->nullable()->comment('Ordem das alternativas (ranking)');
            $table->json('sjt_pair_json')->nullable()
                  ->comment('Para sjt_pair: {best_option_id: X, worst_option_id: Y}');
            $table->unsignedSmallInteger('response_time_seconds')->nullable()
                  ->comment('Tempo em segundos para responder este item (índice de qualidade)');
            $table->timestamps();

            $table->unique(['assessment_application_id', 'assessment_question_id'],
                           'unique_application_question');
            $table->index('assessment_application_id');
            $table->index('assessment_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_responses');
    }
};
