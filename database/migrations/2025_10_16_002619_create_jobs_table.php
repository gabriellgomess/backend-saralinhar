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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('company');
            $table->string('address');
            $table->text('description');
            $table->text('responsibilities');
            $table->text('requirements');
            $table->string('workload');
            $table->decimal('salary', 10, 2)->nullable();
            $table->text('benefits')->nullable();
            $table->string('type'); // clt, pj, estagio, aprendiz
            $table->string('email');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
