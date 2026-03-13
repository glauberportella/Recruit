<?php

namespace App\Observers;

use App\Jobs\ProcessCandidateMatching;
use App\Models\JobCandidates;
use App\Settings\AiMatchingSettings;

class JobCandidatesObserver
{
    public function created(JobCandidates $jobCandidate): void
    {
        try {
            $settings = app(AiMatchingSettings::class);

            if ($settings->auto_match_enabled && $jobCandidate->candidate && $jobCandidate->JobId) {
                ProcessCandidateMatching::dispatch($jobCandidate->JobId, $jobCandidate->candidate)
                    ->delay(now()->addSeconds(30));
            }
        } catch (\Throwable) {
            // Settings table may not be migrated yet
        }
    }
}
