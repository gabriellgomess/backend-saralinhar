<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Áreas e perguntas de entrevista do app EntrevistaPro AI.
     * Migration aditiva: apenas cria tabelas novas, não altera nada existente.
     */
    public function up(): void
    {
        Schema::create('interview_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable()->comment('Nome do ícone Ionicons exibido no app');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_area_id')
                ->nullable()
                ->comment('NULL = pergunta geral, exibida em todas as áreas')
                ->constrained('interview_areas')
                ->cascadeOnDelete();
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
        Schema::dropIfExists('interview_areas');
    }
};
