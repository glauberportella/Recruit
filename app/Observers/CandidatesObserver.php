<?php

namespace App\Observers;

use App\Jobs\GenerateEmbedding;
use App\Models\Candidates;
use App\Settings\AiMatchingSettings;

class CandidatesObserver
{
    public function created(Candidates $candidate): void
    {
        $this->dispatchEmbedding($candidate);
    }

    public function updated(Candidates $candidate): void
    {
        // Only regenerate if relevant fields changed
        $relevantFields = ['SkillSet', 'ExperienceInYears', 'CurrentJobTitle', 'CurrentEmployer',
            'HighestQualificationHeld', 'ExperienceDetails', 'School', 'ExpectedSalary'];

        if ($candidate->wasChanged($relevantFields)) {
            $this->dispatchEmbedding($candidate);
        }
    }

    protected function dispatchEmbedding(Candidates $candidate): void
    {
        try {
            $settings = app(AiMatchingSettings::class);
            if (! empty($settings->openai_api_key)) {
                GenerateEmbedding::dispatch(Candidates::class, $candidate->id)
                    ->delay(now()->addSeconds(10));
            }
        } catch (\Throwable) {
            // Settings table may not be migrated yet
        }
    }
}
