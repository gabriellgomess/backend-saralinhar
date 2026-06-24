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
        Schema::create('disc_test_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Usuário que criou o token');
            $table->string('token', 64)->unique()->comment('Token único para acesso ao teste');
            $table->string('testee_name')->nullable()->comment('Nome do testado (opcional)');
            $table->string('testee_email')->nullable()->comment('Email do testado (opcional)');
            $table->string('testee_phone')->nullable()->comment('Telefone do testado (opcional)');
            $table->string('testee_position')->nullable()->comment('Cargo/Posição do testado (opcional)');
            $table->string('job_title')->nullable()->comment('Título da vaga relacionada');
            $table->text('description')->nullable()->comment('Descrição ou observações');
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active')->comment('Status do token');
            $table->timestamp('expires_at')->nullable()->comment('Data de expiração do token');
            $table->timestamp('used_at')->nullable()->comment('Data de uso do token');
            $table->foreignId('disc_test_result_id')->nullable()->constrained()->onDelete('set null')->comment('Resultado do teste quando usado');
            $table->timestamps();

            $table->index('token');
            $table->index('status');
            $table->index('expires_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disc_test_tokens');
    }
};
