<?php

namespace App\Jobs;

use App\Models\CandidateMatchScore;
use App\Models\CandidateUser;
use App\Models\Candidates;
use App\Notifications\Candidates\NewMatchingJobsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMatchingJobsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $candidateId,
    ) {}

    public function handle(): void
    {
        $candidate = Candidates::find($this->candidateId);

        if (! $candidate) {
            return;
        }

        $candidateUser = CandidateUser::where('email', $candidate->email)->first();

        if (! $candidateUser) {
            return;
        }

        $newMatches = CandidateMatchScore::where('candidate_id', $this->candidateId)
            ->where('overall_score', '>=', 50)
            ->where('matched_at', '>=', now()->subDay())
            ->with('jobOpening')
            ->orderByDesc('overall_score')
            ->get();

        if ($newMatches->isEmpty()) {
            return;
        }

        $candidateUser->notify(new NewMatchingJobsNotification($newMatches));
    }
}
