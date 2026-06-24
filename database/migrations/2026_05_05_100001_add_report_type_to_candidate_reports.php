<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_reports', function (Blueprint $table) {
            $table->string('report_type', 20)->default('sara')->after('id');
            $table->index('report_type');
        });

        // Backfill explícito (todos existentes ficam como 'sara')
        DB::table('candidate_reports')->update(['report_type' => 'sara']);
    }

    public function down(): void
    {
        Schema::table('candidate_reports', function (Blueprint $table) {
            $table->dropIndex(['report_type']);
            $table->dropColumn('report_type');
        });
    }
};
