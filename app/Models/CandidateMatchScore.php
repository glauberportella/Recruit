<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateMatchScore extends Model
{
    protected $fillable = [
        'candidate_id',
        'job_opening_id',
        'overall_score',
        'skills_score',
        'experience_score',
        'education_score',
        'salary_score',
        'skill_gap_analysis',
        'matching_details',
        'matched_at',
    ];

    protected $casts = [
        'skill_gap_analysis' => 'array',
        'matching_details' => 'array',
        'overall_score' => 'decimal:2',
        'skills_score' => 'decimal:2',
        'experience_score' => 'decimal:2',
        'education_score' => 'decimal:2',
        'salary_score' => 'decimal:2',
        'matched_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidates::class, 'candidate_id');
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpenings::class, 'job_opening_id');
    }
}
