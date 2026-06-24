<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('disc_test_tokens', function (Blueprint $table) {
            $table->foreignId('recruitment_client_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('recruitment_clients')
                ->nullOnDelete();
        });

        Schema::table('culture_fit_test_tokens', function (Blueprint $table) {
            $table->foreignId('recruitment_client_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('recruitment_clients')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disc_test_tokens', function (Blueprint $table) {
            $table->dropForeign(['recruitment_client_id']);
            $table->dropColumn('recruitment_client_id');
        });

        Schema::table('culture_fit_test_tokens', function (Blueprint $table) {
            $table->dropForeign(['recruitment_client_id']);
            $table->dropColumn('recruitment_client_id');
        });
    }
};
