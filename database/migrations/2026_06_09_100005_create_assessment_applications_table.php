<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('recruitment_client_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Empresa que solicitou a aplicação (scoping multitenant)');
            $table->foreignId('candidate_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Candidato do banco de currículos, quando aplicável');
            $table->string('respondent_name')->nullable()->comment('Nome do respondente (pode não ser candidato cadastrado)');
            $table->string('respondent_email')->nullable();
            $table->enum('application_type', ['candidate', 'employee', 'leader', 'team', 'climate'])
                  ->default('candidate');
            $table->enum('status', ['pending', 'started', 'completed', 'expired'])->default('pending');
            $table->string('token', 64)->unique()->nullable()->comment('Token de acesso público ao formulário');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable()->comment('Dados extras: cargo, setor, unidade, vaga relacionada etc.');
            $table->timestamps();

            $table->index('assessment_test_id');
            $table->index('recruitment_client_id');
            $table->index('candidate_id');
            $table->index('status');
            $table->index('token');
            $table->index('respondent_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_applications');
    }
};
