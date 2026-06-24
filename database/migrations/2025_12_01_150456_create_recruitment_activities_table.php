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
        Schema::create('recruitment_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('recruitment_clients')->onDelete('cascade');
            $table->string('job_title');
            $table->date('opening_date');
            $table->date('sla_deadline')->nullable();
            $table->date('feedback_sent_date')->nullable();
            $table->date('hiring_date')->nullable();
            $table->string('candidate_name')->nullable();
            $table->string('candidate_contact')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->decimal('commission_value', 10, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->date('feedback_30_days_date')->nullable();
            $table->boolean('replacement_45_days')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_activities');
    }
};
