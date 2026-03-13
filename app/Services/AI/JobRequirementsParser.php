<?php

namespace App\Services\AI;

use App\Models\JobOpenings;

class JobRequirementsParser
{
    public function extractRequiredSkills(JobOpenings $job): array
    {
        $skills = [];

        if (is_array($job->RequiredSkill)) {
            foreach ($job->RequiredSkill as $skill) {
                $skills[] = is_string($skill) ? $skill : (string) $skill;
            }
        }

        return $skills;
    }

    public function extractExperienceRequirement(JobOpenings $job): ?string
    {
        return $job->WorkExperience;
    }

    public function extractJobDescription(JobOpenings $job): string
    {
        return strip_tags($job->JobDescription ?? '');
    }

    public function extractJobRequirements(JobOpenings $job): string
    {
        return strip_tags($job->JobRequirement ?? '');
    }

    public function buildJobProfile(JobOpenings $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->JobTitle,
            'posting_title' => $job->postingTitle,
            'required_skills' => $this->extractRequiredSkills($job),
            'experience_required' => $this->extractExperienceRequirement($job),
            'description' => $this->extractJobDescription($job),
            'requirements' => $this->extractJobRequirements($job),
            'salary' => $job->Salary,
            'job_type' => $job->JobType,
            'remote' => (bool) $job->RemoteJob,
            'location' => [
                'city' => $job->City,
                'state' => $job->State,
                'country' => $job->Country,
            ],
        ];
    }
}
