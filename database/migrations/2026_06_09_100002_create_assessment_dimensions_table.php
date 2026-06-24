<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_test_id')->constrained()->onDelete('cascade');
            $table->string('slug')->comment('Identificador da dimensão, ex: comunicacao-profissional');
            $table->string('name')->comment('Nome exibido no relatório');
            $table->text('description')->nullable();
            $table->decimal('weight', 4, 2)->default(1.00)->comment('Peso da dimensão no score geral');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['assessment_test_id', 'slug']);
            $table->index('assessment_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_dimensions');
    }
};
