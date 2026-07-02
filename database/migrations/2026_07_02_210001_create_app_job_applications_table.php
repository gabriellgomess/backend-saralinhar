<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Candidaturas pessoais acompanhadas pelo candidato no app EntrevistaPro AI.
     * Separada de candidate_job_applications (vagas internas do site).
     */
    public function up(): void
    {
        Schema::create('app_job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company');
            $table->string('role');
            $table->date('applied_at');
            $table->string('status')->default('curriculo_enviado');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_job_applications');
    }
};
