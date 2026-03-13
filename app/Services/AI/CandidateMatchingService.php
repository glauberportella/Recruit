<?php

namespace App\Services\AI;

use App\Models\CandidateMatchScore;
use App\Models\Candidates;
use App\Models\JobOpenings;
use App\Settings\AiMatchingSettings;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class CandidateMatchingService
{
    public function __construct(
        protected CandidateDataExtractor $candidateExtractor,
        protected JobRequirementsParser $jobParser,
        protected AiMatchingSettings $settings,
        protected EmbeddingService $embeddingService,
    ) {}

    public function matchCandidateToJob(Candidates $candidate, JobOpenings $job, ?float $vectorSimilarity = null): CandidateMatchScore
    {
        $candidateProfile = $this->candidateExtractor->buildCandidateProfile($candidate);
        $jobProfile = $this->jobParser->buildJobProfile($job);

        $aiAnalysis = $this->performAiAnalysis($candidateProfile, $jobProfile);

        if ($vectorSimilarity !== null) {
            $aiAnalysis['vector_similarity'] = round($vectorSimilarity, 4);
        }

        $scores = $this->calculateScores($aiAnalysis);
        $overallScore = $this->calculateOverallScore($scores);

        // Boost or adjust score based on vector similarity if available
        if ($vectorSimilarity !== null) {
            // Blend: 80% detailed analysis + 20% vector similarity
            $vectorScore = $vectorSimilarity * 100;
            $overallScore = round(($overallScore * 0.8) + ($vectorScore * 0.2), 2);
        }

        $skillGap = $aiAnalysis['skill_gap'] ?? [];

        return CandidateMatchScore::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'job_opening_id' => $job->id,
            ],
            [
                'overall_score' => $overallScore,
                'skills_score' => $scores['skills'],
                'experience_score' => $scores['experience'],
                'education_score' => $scores['education'],
                'salary_score' => $scores['salary'],
                'skill_gap_analysis' => $skillGap,
                'matching_details' => $aiAnalysis,
                'matched_at' => now(),
            ]
        );
    }

    public function matchAllCandidatesToJob(JobOpenings $job): array
    {
        // First, ensure the job has an embedding
        $this->embeddingService->embedJobOpening($job);

        // Use vector similarity to find top candidate matches (pre-filter)
        $similarCandidates = $this->embeddingService->findSimilarCandidates(
            $job,
            limit: 50,
            minSimilarity: 0.3
        );

        $results = [];

        if ($similarCandidates->isNotEmpty()) {
            // Fetch candidates ranked by vector similarity
            $candidateIds = $similarCandidates->pluck('candidate_id')->toArray();
            $candidates = Candidates::whereIn('id', $candidateIds)->get()->keyBy('id');

            foreach ($similarCandidates as $match) {
                $candidate = $candidates->get($match->candidate_id);
                if (! $candidate) {
                    continue;
                }

                try {
                    $results[] = $this->matchCandidateToJob($candidate, $job, $match->similarity);
                } catch (\Throwable $e) {
                    Log::error("AI Matching failed for candidate {$candidate->id} and job {$job->id}: {$e->getMessage()}");
                }
            }
        } else {
            // Fallback: if no embeddings available, match all candidates
            $candidates = Candidates::all();
            foreach ($candidates as $candidate) {
                try {
                    $results[] = $this->matchCandidateToJob($candidate, $job);
                } catch (\Throwable $e) {
                    Log::error("AI Matching failed for candidate {$candidate->id} and job {$job->id}: {$e->getMessage()}");
                }
            }
        }

        return $results;
    }

    public function matchCandidateToAllJobs(Candidates $candidate): array
    {
        // First, ensure the candidate has an embedding
        $this->embeddingService->embedCandidate($candidate);

        // Use vector similarity to find top job matches (pre-filter)
        $similarJobs = $this->embeddingService->findSimilarJobs(
            $candidate,
            limit: 50,
            minSimilarity: 0.3
        );

        $results = [];

        if ($similarJobs->isNotEmpty()) {
            $jobIds = $similarJobs->pluck('job_opening_id')->toArray();
            $jobs = JobOpenings::jobStillOpen()->whereIn('id', $jobIds)->get()->keyBy('id');

            foreach ($similarJobs as $match) {
                $job = $jobs->get($match->job_opening_id);
                if (! $job) {
                    continue;
                }

                try {
                    $results[] = $this->matchCandidateToJob($candidate, $job, $match->similarity);
                } catch (\Throwable $e) {
                    Log::error("AI Matching failed for candidate {$candidate->id} and job {$job->id}: {$e->getMessage()}");
                }
            }
        } else {
            // Fallback: if no embeddings available, match all open jobs
            $jobs = JobOpenings::jobStillOpen()->get();
            foreach ($jobs as $job) {
                try {
                    $results[] = $this->matchCandidateToJob($candidate, $job);
                } catch (\Throwable $e) {
                    Log::error("AI Matching failed for candidate {$candidate->id} and job {$job->id}: {$e->getMessage()}");
                }
            }
        }

        return $results;
    }

    public function getTopCandidatesForJob(JobOpenings $job, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return CandidateMatchScore::where('job_opening_id', $job->id)
            ->where('overall_score', '>=', $this->settings->minimum_match_score)
            ->orderByDesc('overall_score')
            ->with('candidate')
            ->limit($limit)
            ->get();
    }

    public function getRecommendedJobsForCandidate(Candidates $candidate, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return CandidateMatchScore::where('candidate_id', $candidate->id)
            ->where('overall_score', '>=', $this->settings->minimum_match_score)
            ->orderByDesc('overall_score')
            ->with('jobOpening')
            ->limit($limit)
            ->get();
    }

    protected function performAiAnalysis(array $candidateProfile, array $jobProfile): array
    {
        if (empty($this->settings->openai_api_key)) {
            return $this->performLocalAnalysis($candidateProfile, $jobProfile);
        }

        try {
            return $this->performOpenAiAnalysis($candidateProfile, $jobProfile);
        } catch (\Throwable $e) {
            Log::warning("OpenAI analysis failed, falling back to local: {$e->getMessage()}");

            return $this->performLocalAnalysis($candidateProfile, $jobProfile);
        }
    }

    protected function performOpenAiAnalysis(array $candidateProfile, array $jobProfile): array
    {
        $prompt = $this->buildAnalysisPrompt($candidateProfile, $jobProfile);

        $response = OpenAI::chat()->create([
            'model' => $this->settings->openai_model ?? 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert HR recruitment analyst. Analyze the candidate-job match and return a JSON response with the following structure:
{
  "skills_score": <0-100>,
  "experience_score": <0-100>,
  "education_score": <0-100>,
  "salary_score": <0-100>,
  "skill_gap": [{"skill": "<skill_name>", "status": "match|partial|missing", "notes": "<brief note>"}],
  "strengths": ["<strength1>", "<strength2>"],
  "weaknesses": ["<weakness1>", "<weakness2>"],
  "summary": "<brief summary of the match analysis>"
}
Only return valid JSON, no markdown formatting.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        $content = $response->choices[0]->message->content;

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse OpenAI response as JSON, falling back to local analysis');

            return $this->performLocalAnalysis($candidateProfile, $jobProfile);
        }

        return $decoded;
    }

    protected function buildAnalysisPrompt(array $candidateProfile, array $jobProfile): string
    {
        $candidateSkills = collect($candidateProfile['skills'])->pluck('name')->implode(', ');
        $jobSkills = implode(', ', $jobProfile['required_skills']);

        return <<<PROMPT
Analyze the following candidate-job match:

## Job Opening
- Title: {$jobProfile['title']}
- Required Skills: {$jobSkills}
- Experience Required: {$jobProfile['experience_required']}
- Description: {$jobProfile['description']}
- Requirements: {$jobProfile['requirements']}
- Salary: {$jobProfile['salary']}
- Type: {$jobProfile['job_type']}
- Location: {$jobProfile['location']['city']}, {$jobProfile['location']['state']}, {$jobProfile['location']['country']}

## Candidate
- Name: {$candidateProfile['name']}
- Skills: {$candidateSkills}
- Experience: {$candidateProfile['experience']['total_years']} years
- Current Title: {$candidateProfile['experience']['current_title']}
- Current Employer: {$candidateProfile['experience']['current_employer']}
- Education: {$candidateProfile['education']['highest_qualification']}
- Expected Salary: {$candidateProfile['expected_salary']}
- Location: {$candidateProfile['location']['city']}, {$candidateProfile['location']['state']}, {$candidateProfile['location']['country']}

Provide a detailed match analysis with scores from 0-100 for each category.
PROMPT;
    }

    protected function performLocalAnalysis(array $candidateProfile, array $jobProfile): array
    {
        $skillsScore = $this->calculateSkillsMatchLocal($candidateProfile['skills'], $jobProfile['required_skills']);
        $experienceScore = $this->calculateExperienceMatchLocal($candidateProfile['experience'], $jobProfile['experience_required']);
        $educationScore = $this->calculateEducationMatchLocal($candidateProfile['education']);
        $salaryScore = $this->calculateSalaryMatchLocal($candidateProfile['expected_salary'], $jobProfile['salary']);

        $skillGap = $this->buildSkillGapLocal($candidateProfile['skills'], $jobProfile['required_skills']);

        return [
            'skills_score' => $skillsScore,
            'experience_score' => $experienceScore,
            'education_score' => $educationScore,
            'salary_score' => $salaryScore,
            'skill_gap' => $skillGap,
            'strengths' => $this->identifyStrengthsLocal($candidateProfile, $jobProfile),
            'weaknesses' => $this->identifyWeaknessesLocal($candidateProfile, $jobProfile),
            'summary' => 'Match analysis performed using local algorithm.',
        ];
    }

    protected function calculateSkillsMatchLocal(array $candidateSkills, array $requiredSkills): float
    {
        if (empty($requiredSkills)) {
            return 50.0;
        }

        $candidateSkillNames = array_map(
            fn ($s) => strtolower(is_array($s) ? ($s['name'] ?? '') : (string) $s),
            $candidateSkills
        );

        $matched = 0;
        foreach ($requiredSkills as $required) {
            $requiredLower = strtolower($required);
            foreach ($candidateSkillNames as $candidateSkill) {
                if (str_contains($candidateSkill, $requiredLower) || str_contains($requiredLower, $candidateSkill)) {
                    $matched++;
                    break;
                }
            }
        }

        return round(($matched / count($requiredSkills)) * 100, 2);
    }

    protected function calculateExperienceMatchLocal(array $experience, ?string $required): float
    {
        if (empty($required)) {
            return 70.0;
        }

        $candidateYears = $this->parseYearsFromString($experience['total_years'] ?? '0');
        $requiredYears = $this->parseExperienceRequirement($required);

        if ($requiredYears <= 0) {
            return 70.0;
        }

        $ratio = $candidateYears / $requiredYears;

        if ($ratio >= 1.0) {
            return min(100, 80 + ($ratio - 1.0) * 20);
        }

        return max(0, $ratio * 80);
    }

    protected function calculateEducationMatchLocal(array $education): float
    {
        $qualificationScores = [
            'doctorate degree' => 100,
            'masters degree' => 85,
            'bachelors degree' => 70,
            'associates degree' => 55,
            'secondary/high school' => 40,
        ];

        $qualification = strtolower($education['highest_qualification'] ?? '');

        return $qualificationScores[$qualification] ?? 50.0;
    }

    protected function calculateSalaryMatchLocal(?string $expected, ?string $offered): float
    {
        if (empty($expected) || empty($offered)) {
            return 70.0;
        }

        $expectedNum = (float) preg_replace('/[^0-9.]/', '', $expected);
        $offeredNum = (float) preg_replace('/[^0-9.]/', '', $offered);

        if ($expectedNum <= 0 || $offeredNum <= 0) {
            return 70.0;
        }

        if ($offeredNum >= $expectedNum) {
            return 100.0;
        }

        $ratio = $offeredNum / $expectedNum;

        return max(0, round($ratio * 100, 2));
    }

    protected function buildSkillGapLocal(array $candidateSkills, array $requiredSkills): array
    {
        $candidateSkillNames = array_map(
            fn ($s) => strtolower(is_array($s) ? ($s['name'] ?? '') : (string) $s),
            $candidateSkills
        );

        $gap = [];
        foreach ($requiredSkills as $required) {
            $requiredLower = strtolower($required);
            $found = false;
            $partial = false;

            foreach ($candidateSkillNames as $candidateSkill) {
                if ($candidateSkill === $requiredLower) {
                    $found = true;
                    break;
                }
                if (str_contains($candidateSkill, $requiredLower) || str_contains($requiredLower, $candidateSkill)) {
                    $partial = true;
                }
            }

            if ($found) {
                $status = 'match';
                $notes = 'Candidate has this skill';
            } elseif ($partial) {
                $status = 'partial';
                $notes = 'Candidate has a related skill';
            } else {
                $status = 'missing';
                $notes = 'Candidate lacks this skill';
            }

            $gap[] = [
                'skill' => $required,
                'status' => $status,
                'notes' => $notes,
            ];
        }

        return $gap;
    }

    protected function identifyStrengthsLocal(array $candidateProfile, array $jobProfile): array
    {
        $strengths = [];

        $candidateSkillNames = array_map(
            fn ($s) => strtolower(is_array($s) ? ($s['name'] ?? '') : (string) $s),
            $candidateProfile['skills']
        );
        $matchedSkills = 0;
        foreach ($jobProfile['required_skills'] as $required) {
            foreach ($candidateSkillNames as $skill) {
                if (str_contains($skill, strtolower($required)) || str_contains(strtolower($required), $skill)) {
                    $matchedSkills++;
                    break;
                }
            }
        }

        if ($matchedSkills > 0) {
            $strengths[] = "{$matchedSkills} of " . count($jobProfile['required_skills']) . ' required skills matched';
        }

        if (count($candidateProfile['skills']) > count($jobProfile['required_skills'])) {
            $strengths[] = 'Candidate has a broader skill set than required';
        }

        if (! empty($candidateProfile['experience']['current_title'])) {
            $strengths[] = 'Currently employed as ' . $candidateProfile['experience']['current_title'];
        }

        return $strengths;
    }

    protected function identifyWeaknessesLocal(array $candidateProfile, array $jobProfile): array
    {
        $weaknesses = [];

        $candidateSkillNames = array_map(
            fn ($s) => strtolower(is_array($s) ? ($s['name'] ?? '') : (string) $s),
            $candidateProfile['skills']
        );
        $missingSkills = [];
        foreach ($jobProfile['required_skills'] as $required) {
            $found = false;
            foreach ($candidateSkillNames as $skill) {
                if (str_contains($skill, strtolower($required)) || str_contains(strtolower($required), $skill)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $missingSkills[] = $required;
            }
        }

        if (! empty($missingSkills)) {
            $weaknesses[] = 'Missing skills: ' . implode(', ', $missingSkills);
        }

        $expectedNum = (float) preg_replace('/[^0-9.]/', '', $candidateProfile['expected_salary'] ?? '0');
        $offeredNum = (float) preg_replace('/[^0-9.]/', '', $jobProfile['salary'] ?? '0');

        if ($expectedNum > 0 && $offeredNum > 0 && $expectedNum > $offeredNum * 1.2) {
            $weaknesses[] = 'Salary expectation exceeds offered range';
        }

        return $weaknesses;
    }

    protected function calculateScores(array $analysis): array
    {
        return [
            'skills' => min(100, max(0, (float) ($analysis['skills_score'] ?? 0))),
            'experience' => min(100, max(0, (float) ($analysis['experience_score'] ?? 0))),
            'education' => min(100, max(0, (float) ($analysis['education_score'] ?? 0))),
            'salary' => min(100, max(0, (float) ($analysis['salary_score'] ?? 0))),
        ];
    }

    protected function calculateOverallScore(array $scores): float
    {
        return round(
            ($scores['skills'] * $this->settings->skills_weight) +
            ($scores['experience'] * $this->settings->experience_weight) +
            ($scores['education'] * $this->settings->education_weight) +
            ($scores['salary'] * $this->settings->salary_weight),
            2
        );
    }

    protected function parseYearsFromString(?string $value): float
    {
        if (empty($value)) {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $map = [
            '1year' => 1, '2years' => 2, '3years' => 3, '4years' => 4, '5years+' => 6,
            '1 year' => 1, '2 years' => 2, '3 years' => 3, '4 years' => 4, '5+ years' => 6,
        ];

        return $map[strtolower(trim($value))] ?? 0;
    }

    protected function parseExperienceRequirement(?string $value): float
    {
        if (empty($value)) {
            return 0;
        }

        $map = [
            'refresher' => 0, '0_1year' => 1, '1_3years' => 2, '4_5years' => 4.5, '5+years' => 5,
        ];

        return $map[strtolower(trim($value))] ?? 0;
    }
}
