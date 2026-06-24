<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, processing, done
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->timestamps();
        });

        Schema::create('batch_upload_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_upload_id')->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('temp_path');
            $table->string('status')->default('queued'); // queued, processing, done, error, confirmed
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('professional_area')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_upload_files');
        Schema::dropIfExists('batch_uploads');
    }
};
