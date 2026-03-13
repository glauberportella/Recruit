<?php

namespace App\Jobs;

use App\Models\Candidates;
use App\Services\AI\CandidateMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCandidateJobSuggestions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $candidateId,
    ) {}

    public function handle(CandidateMatchingService $matchingService): void
    {
        $candidate = Candidates::findOrFail($this->candidateId);
        $matchingService->matchCandidateToAllJobs($candidate);

        SendMatchingJobsNotification::dispatch($this->candidateId)->delay(now()->addMinutes(2));
    }
}
