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
            $table->string('testee_name')->after('user_id')->nullable()->comment('Nome do testado');
            $table->string('testee_email')->after('testee_name')->nullable()->comment('Email do testado');
            $table->string('testee_phone')->after('testee_email')->nullable()->comment('Telefone do testado');
            $table->string('testee_position')->after('testee_phone')->nullable()->comment('Cargo/Posição do testado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disc_test_results', function (Blueprint $table) {
            $table->dropColumn(['testee_name', 'testee_email', 'testee_phone', 'testee_position']);
        });
    }
};
