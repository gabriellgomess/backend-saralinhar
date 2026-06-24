<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('recruitment_client_id')->nullable()->after('role');
            $table->foreign('recruitment_client_id')->references('id')->on('recruitment_clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['recruitment_client_id']);
            $table->dropColumn('recruitment_client_id');
        });
    }
};
