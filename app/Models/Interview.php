<?php

namespace App\Models;

use App\Filament\Enums\InterviewStatus;
use App\Services\Jitsi\JitsiService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Interview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_candidate_id',
        'scheduled_by',
        'title',
        'description',
        'scheduled_at',
        'duration_minutes',
        'meeting_room',
        'status',
        'interviewer_notes',
        'candidate_notes',
        'rating',
        'feedback',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'feedback' => 'array',
        'status' => InterviewStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Interview $interview) {
            if (empty($interview->meeting_room)) {
                $interview->meeting_room = 'recruit-' . Str::random(12);
            }
        });
    }

    public function jobCandidate(): BelongsTo
    {
        return $this->belongsTo(JobCandidates::class, 'job_candidate_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function isUpcoming(): bool
    {
        return $this->status === InterviewStatus::Scheduled
            && $this->scheduled_at->isFuture();
    }

    public function isJoinable(): bool
    {
        if ($this->status === InterviewStatus::Cancelled || $this->status === InterviewStatus::Completed) {
            return false;
        }

        $start = $this->scheduled_at->subMinutes(10);
        $end = $this->scheduled_at->addMinutes($this->duration_minutes + 30);

        return now()->between($start, $end);
    }

    public function getInterviewerMeetingUrl(): string
    {
        $jitsi = app(JitsiService::class);
        $user = $this->scheduler;

        return $jitsi->getMeetingUrl($this, [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ], true);
    }

    public function getCandidateMeetingUrl(): string
    {
        $jitsi = app(JitsiService::class);
        $candidate = $this->jobCandidate->candidateProfile;

        return $jitsi->getMeetingUrl($this, [
            'id' => 'candidate-' . $candidate->id,
            'name' => $candidate->FirstName . ' ' . $candidate->LastName,
            'email' => $candidate->email,
        ], false);
    }
}
