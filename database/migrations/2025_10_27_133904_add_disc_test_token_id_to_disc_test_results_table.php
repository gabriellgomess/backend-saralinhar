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
        Schema::table('disc_test_results', function (Blueprint $table) {
            $table->foreignId('disc_test_token_id')->nullable()->constrained()->onDelete('set null')->comment('Token usado para realizar o teste');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disc_test_results', function (Blueprint $table) {
            $table->dropForeign(['disc_test_token_id']);
            $table->dropColumn('disc_test_token_id');
        });
    }
};
