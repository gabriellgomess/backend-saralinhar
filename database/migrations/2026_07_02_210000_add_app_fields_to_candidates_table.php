<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos do perfil profissional do app EntrevistaPro AI.
     * Migration ADITIVA: só adiciona colunas nullable, não altera dados existentes.
     */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
            $table->string('desired_role')->nullable();
            $table->string('work_mode')->nullable()->comment('presencial|hibrido|remoto');
            $table->text('education')->nullable();
            $table->text('skills')->nullable();
            $table->string('salary_expectation')->nullable();
            $table->text('summary')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'desired_role',
                'work_mode',
                'education',
                'skills',
                'salary_expectation',
                'summary',
            ]);
        });
    }
};
