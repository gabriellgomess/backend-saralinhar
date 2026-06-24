<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recruitment_client_id')->nullable()->constrained('recruitment_clients')->nullOnDelete();

            $table->string('feature', 64);
            $table->string('model', 64);
            $table->string('endpoint', 64);

            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('cached_input_tokens')->nullable();
            $table->unsignedInteger('reasoning_tokens')->nullable();

            $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('status', 16);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();

            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('response_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('feature');
            $table->index('model');
            $table->index('status');
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['feature', 'created_at']);
            $table->index(['recruitment_client_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openai_usage_logs');
    }
};
