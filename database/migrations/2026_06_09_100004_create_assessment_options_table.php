<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_question_id')->constrained()->onDelete('cascade');
            $table->string('label')->nullable()->comment('Rótulo curto da alternativa, ex: A, B, C');
            $table->text('text')->comment('Texto completo da alternativa exibido ao respondente');
            $table->unsignedTinyInteger('score')->default(0)
                  ->comment('Score da alternativa (0-100) para SJT; não expor ao frontend');
            $table->string('competency_target')->nullable()
                  ->comment('Dimensão/competência que esta alternativa avalia (SJT)');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index('assessment_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_options');
    }
};
