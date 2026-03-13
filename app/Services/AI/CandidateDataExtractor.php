<?php

namespace App\Services\AI;

use App\Models\Candidates;
use App\Models\JobOpenings;

class CandidateDataExtractor
{
    public function extractSkills(Candidates $candidate): array
    {
        $skills = [];

        if (is_array($candidate->SkillSet)) {
            foreach ($candidate->SkillSet as $skillEntry) {
                if (is_array($skillEntry) && isset($skillEntry['skill'])) {
                    $skills[] = [
                        'name' => $skillEntry['skill'],
                        'proficiency' => $skillEntry['proficiency'] ?? null,
                        'experience_years' => $skillEntry['experience_years'] ?? null,
                    ];
                } elseif (is_string($skillEntry)) {
                    $skills[] = ['name' => $skillEntry, 'proficiency' => null, 'experience_years' => null];
                }
            }
        }

        return $skills;
    }

    public function extractExperience(Candidates $candidate): array
    {
        $experience = [
            'total_years' => $candidate->ExperienceInYears,
            'current_title' => $candidate->CurrentJobTitle,
            'current_employer' => $candidate->CurrentEmployer,
            'details' => [],
        ];

        if (is_array($candidate->ExperienceDetails)) {
            foreach ($candidate->ExperienceDetails as $entry) {
                $experience['details'][] = [
                    'company' => $entry['company_name'] ?? null,
                    'role' => $entry['role'] ?? null,
                    'duration' => $entry['duration'] ?? null,
                    'is_current' => $entry['current'] ?? false,
                ];
            }
        }

        return $experience;
    }

    public function extractEducation(Candidates $candidate): array
    {
        $education = [
            'highest_qualification' => $candidate->HighestQualificationHeld,
            'schools' => [],
        ];

        if (is_array($candidate->School)) {
            foreach ($candidate->School as $school) {
                $education['schools'][] = [
                    'name' => $school['school_name'] ?? null,
                    'major' => $school['major'] ?? null,
                    'duration' => $school['duration'] ?? null,
                    'pursuing' => $school['pursuing'] ?? false,
                ];
            }
        }

        return $education;
    }

    public function buildCandidateProfile(Candidates $candidate): array
    {
        return [
            'id' => $candidate->id,
            'name' => $candidate->FirstName . ' ' . $candidate->LastName,
            'skills' => $this->extractSkills($candidate),
            'experience' => $this->extractExperience($candidate),
            'education' => $this->extractEducation($candidate),
            'expected_salary' => $candidate->ExpectedSalary,
            'current_salary' => $candidate->CurrentSalary,
            'location' => [
                'city' => $candidate->City,
                'state' => $candidate->State,
                'country' => $candidate->Country,
            ],
        ];
    }
}
