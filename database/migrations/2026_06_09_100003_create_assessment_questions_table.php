<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_dimension_id')->nullable()->constrained()->onDelete('set null');
            $table->string('code')->nullable()->comment('Código interno da pergunta, ex: COM_01');
            $table->text('statement')->comment('Texto da pergunta exibido ao respondente');
            $table->enum('question_type', ['likert', 'single_choice', 'ranking', 'enps', 'open_text', 'sjt_pair'])
                  ->comment('Formato da resposta esperada');
            $table->unsignedTinyInteger('scale_min')->default(1)->comment('Valor mínimo da escala (Likert/eNPS)');
            $table->unsignedTinyInteger('scale_max')->default(5)->comment('Valor máximo da escala (Likert/eNPS)');
            $table->boolean('is_reverse')->default(false)->comment('Item reverso: resposta_ajustada = (scale_max+1) - resposta_original');
            $table->decimal('weight', 4, 2)->default(1.00)->comment('Peso do item dentro da dimensão');
            $table->boolean('is_attention_check')->default(false)->comment('Item de verificação de atenção');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index('assessment_test_id');
            $table->index('assessment_dimension_id');
            $table->index('question_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
    }
};
