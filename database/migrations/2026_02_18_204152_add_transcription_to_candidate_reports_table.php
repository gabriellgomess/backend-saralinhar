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
        Schema::table('candidate_reports', function (Blueprint $table) {
            $table->longText('transcription')->nullable()->after('audio_path');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_reports', function (Blueprint $table) {
            $table->dropColumn('transcription');
        });
    }
};
