<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('recruitment_clients')->onDelete('cascade');
            $table->enum('type', ['recruitment', 'service']);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->onDelete('set null');
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->onDelete('set null');
            $table->decimal('candidate_salary', 10, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->nullable();

            $table->foreignId('financial_service_id')->nullable()->constrained('financial_services')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
