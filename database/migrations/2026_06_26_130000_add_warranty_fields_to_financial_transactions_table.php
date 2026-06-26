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
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->date('admission_date')->nullable()->after('due_date');
            $table->string('candidate_contact')->nullable()->after('candidate_id');
            $table->date('warranty_ends_at')->nullable()->after('admission_date');
            $table->boolean('is_warranty_replacement')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'admission_date',
                'candidate_contact',
                'warranty_ends_at',
                'is_warranty_replacement',
            ]);
        });
    }
};
