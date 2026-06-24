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
            $table->string('testee_cpf')->nullable()->after('testee_email')->comment('CPF do testado');
        });

        Schema::table('culture_fit_results', function (Blueprint $table) {
            $table->string('testee_cpf')->nullable()->after('testee_email')->comment('CPF do testado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disc_test_results', function (Blueprint $table) {
            $table->dropColumn('testee_cpf');
        });

        Schema::table('culture_fit_results', function (Blueprint $table) {
            $table->dropColumn('testee_cpf');
        });
    }
};
