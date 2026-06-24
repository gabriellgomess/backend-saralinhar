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
            $table->enum('pipeline_stage', [
                'new', 
                'contacting', 
                'interview_scheduled', 
                'interviewed', 
                'shortlisted', 
                'rejected', 
                'hired'
            ])->default('new')->after('status');
            
            $table->dateTime('interview_date')->nullable()->after('pipeline_stage');
            $table->text('interview_feedback')->nullable()->after('interview_date');
            $table->text('admin_notes')->nullable()->after('interview_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_job_applications', function (Blueprint $table) {
            $table->dropColumn(['pipeline_stage', 'interview_date', 'interview_feedback', 'admin_notes']);
        });
    }
};
