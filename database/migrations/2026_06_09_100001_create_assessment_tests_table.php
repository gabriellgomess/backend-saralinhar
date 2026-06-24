<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_tests', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->comment('Identificador único do instrumento, ex: competencias-comportamentais');
            $table->string('name')->comment('Nome do instrumento exibido na plataforma');
            $table->text('description')->nullable();
            $table->enum('type', ['likert', 'sjt', 'climate', 'hybrid'])->comment('Tipo do instrumento');
            $table->string('version', 10)->default('1.0')->comment('Versão do instrumento; não alterar perguntas de versão ativa');
            $table->text('disclaimer')->nullable()->comment('Aviso metodológico exibido no início e no relatório');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable()->comment('Parâmetros extras da estratégia de cálculo');
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_tests');
    }
};
