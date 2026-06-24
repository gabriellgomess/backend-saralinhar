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
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('original_email')->nullable()->after('phone');
            $table->string('original_phone')->nullable()->after('original_email');
            $table->string('source')->nullable()->after('original_phone');
            $table->string('reference_id')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['original_email', 'original_phone', 'source', 'reference_id']);
        });
    }
};
