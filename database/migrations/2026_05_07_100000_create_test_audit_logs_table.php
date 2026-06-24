<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('recruitment_client_id')->nullable();

            $table->string('test_type', 32);
            $table->string('action', 64);

            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('recruitment_client_id');
            $table->index('test_type');
            $table->index('action');
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recruitment_client_id')->references('id')->on('recruitment_clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_audit_logs');
    }
};
