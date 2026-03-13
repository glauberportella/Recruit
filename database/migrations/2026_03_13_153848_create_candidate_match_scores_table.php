<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_match_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->decimal('skills_score', 5, 2)->default(0);
            $table->decimal('experience_score', 5, 2)->default(0);
            $table->decimal('education_score', 5, 2)->default(0);
            $table->decimal('salary_score', 5, 2)->default(0);
            $table->json('skill_gap_analysis')->nullable();
            $table->json('matching_details')->nullable();
            $table->timestamp('matched_at')->useCurrent();
            $table->timestamps();

            $table->unique(['candidate_id', 'job_opening_id']);
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_match_scores');
    }
};
