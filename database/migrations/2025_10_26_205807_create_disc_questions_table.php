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
        Schema::create('disc_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('question_number');
            $table->text('statement_d')->comment('Afirmação perfil Dominância');
            $table->text('statement_i')->comment('Afirmação perfil Influência');
            $table->text('statement_s')->comment('Afirmação perfil Estabilidade');
            $table->text('statement_c')->comment('Afirmação perfil Conformidade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('question_number');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disc_questions');
    }
};
