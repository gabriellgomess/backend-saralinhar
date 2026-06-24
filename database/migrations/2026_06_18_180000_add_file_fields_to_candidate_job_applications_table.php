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
        Schema::table('candidate_job_applications', function (Blueprint $table) {
            $table->string('parecer_file_path')->nullable()->after('admin_notes');
            $table->string('parecer_file_original_name')->nullable()->after('parecer_file_path');
            
            $table->string('disc_file_path')->nullable()->after('parecer_file_original_name');
            $table->string('disc_file_original_name')->nullable()->after('disc_file_path');
            
            $table->string('culture_fit_file_path')->nullable()->after('disc_file_original_name');
            $table->string('culture_fit_file_original_name')->nullable()->after('culture_fit_file_path');
            
            $table->string('mapeamento_file_path')->nullable()->after('culture_fit_file_original_name');
            $table->string('mapeamento_file_original_name')->nullable()->after('mapeamento_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'parecer_file_path', 'parecer_file_original_name',
                'disc_file_path', 'disc_file_original_name',
                'culture_fit_file_path', 'culture_fit_file_original_name',
                'mapeamento_file_path', 'mapeamento_file_original_name'
            ]);
        });
    }
};
