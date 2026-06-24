<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disc_test_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('candidate_id')->nullable()->after('user_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->nullOnDelete();
        });

        Schema::table('disc_test_results', function (Blueprint $table) {
            $table->unsignedBigInteger('candidate_id')->nullable()->after('user_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disc_test_tokens', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropColumn('candidate_id');
        });

        Schema::table('disc_test_results', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropColumn('candidate_id');
        });
    }
};
