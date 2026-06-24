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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('professional_area')->nullable();
            $table->text('qualifications_summary')->nullable(); // Resumo das qualificações gerado pela IA
            $table->string('file_path'); // Caminho do arquivo do currículo
            $table->string('file_original_name'); // Nome original do arquivo
            $table->enum('status', ['pending', 'analyzed', 'error'])->default('pending');
            $table->timestamps();

            // Índices para otimização de buscas
            $table->index('email');
            $table->index('professional_area');
            $table->index('city');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
