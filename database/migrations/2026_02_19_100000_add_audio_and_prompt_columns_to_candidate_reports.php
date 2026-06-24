<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_reports', function (Blueprint $table) {
            $table->timestamp('audio_expires_at')->nullable()->after('audio_path');
            $table->text('complementary_prompt')->nullable()->after('audio_expires_at');
            $table->unsignedInteger('regeneration_count')->default(0)->after('complementary_prompt');
            $table->timestamp('last_regenerated_at')->nullable()->after('regeneration_count');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_reports', function (Blueprint $table) {
            $table->dropColumn(['audio_expires_at', 'complementary_prompt', 'regeneration_count', 'last_regenerated_at']);
        });
    }
};
