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
        Schema::create('culture_fit_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('question_number');
            $table->text('situation')->comment('Situação/cenário apresentado');
            $table->text('statement')->comment('Afirmação ou pergunta');
            $table->string('dimension', 50)->comment('Dimensão avaliada: autonomy, innovation, hierarchy, teamwork, results, flexibility');
            $table->enum('scoring_direction', ['positive', 'negative'])->default('positive')->comment('Se concordar aumenta ou diminui a pontuação');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('question_number');
            $table->index('dimension');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('culture_fit_questions');
    }
};
