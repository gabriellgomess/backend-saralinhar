<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_clients', function (Blueprint $table) {
            $table->boolean('is_player')->default(false)->after('name');
        });

        // Backfill: marca como player os clientes cujo nome contém "player"
        DB::table('recruitment_clients')
            ->whereRaw('LOWER(name) LIKE ?', ['%player%'])
            ->update(['is_player' => true]);
    }

    public function down(): void
    {
        Schema::table('recruitment_clients', function (Blueprint $table) {
            $table->dropColumn('is_player');
        });
    }
};
