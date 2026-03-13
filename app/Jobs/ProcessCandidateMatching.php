<?php

namespace App\Jobs;

use App\Models\Candidates;
use App\Models\JobOpenings;
use App\Services\AI\CandidateMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCandidateMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $jobOpeningId,
        public readonly ?int $candidateId = null,
    ) {}

    public function handle(CandidateMatchingService $matchingService): void
    {
        $job = JobOpenings::findOrFail($this->jobOpeningId);

        if ($this->candidateId) {
            $candidate = Candidates::findOrFail($this->candidateId);
            $matchingService->matchCandidateToJob($candidate, $job);
        } else {
            $matchingService->matchAllCandidatesToJob($job);
        }
    }
}
