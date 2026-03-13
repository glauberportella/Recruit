<?php

namespace App\Observers;

use App\Jobs\GenerateEmbedding;
use App\Models\JobOpenings;
use App\Settings\AiMatchingSettings;

class JobOpeningsObserver
{
    public function created(JobOpenings $job): void
    {
        $this->dispatchEmbedding($job);
    }

    public function updated(JobOpenings $job): void
    {
        $relevantFields = ['JobDescription', 'JobRequirement', 'RequiredSkill', 'WorkExperience',
            'postingTitle', 'JobTitle', 'Salary', 'JobType', 'City', 'State', 'Country'];

        if ($job->wasChanged($relevantFields)) {
            $this->dispatchEmbedding($job);
        }
    }

    protected function dispatchEmbedding(JobOpenings $job): void
    {
        try {
            $settings = app(AiMatchingSettings::class);
            if (! empty($settings->openai_api_key)) {
                GenerateEmbedding::dispatch(JobOpenings::class, $job->id)
                    ->delay(now()->addSeconds(10));
            }
        } catch (\Throwable) {
            // Settings table may not be migrated yet
        }
    }
}
