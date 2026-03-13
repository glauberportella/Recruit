<?php

namespace App\Services\AI;

use App\Models\Candidates;
use App\Models\Embedding;
use App\Models\JobOpenings;
use App\Settings\AiMatchingSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class EmbeddingService
{
    public function __construct(
        protected CandidateDataExtractor $candidateExtractor,
        protected JobRequirementsParser $jobParser,
        protected AiMatchingSettings $settings,
    ) {}

    /**
     * Generate and store embedding for a candidate.
     */
    public function embedCandidate(Candidates $candidate): ?Embedding
    {
        $text = $this->buildCandidateText($candidate);

        return $this->generateAndStore($candidate, $text);
    }

    /**
     * Generate and store embedding for a job opening.
     */
    public function embedJobOpening(JobOpenings $job): ?Embedding
    {
        $text = $this->buildJobText($job);

        return $this->generateAndStore($job, $text);
    }

    /**
     * Find the most similar candidates for a given job using vector cosine similarity.
     *
     * @return Collection<int, array{candidate_id: int, similarity: float}>
     */
    public function findSimilarCandidates(JobOpenings $job, int $limit = 20, float $minSimilarity = 0.5): Collection
    {
        $jobEmbedding = Embedding::where('embeddable_type', JobOpenings::class)
            ->where('embeddable_id', $job->id)
            ->first();

        if (! $jobEmbedding) {
            $jobEmbedding = $this->embedJobOpening($job);
        }

        if (! $jobEmbedding) {
            return collect();
        }

        // pgvector cosine distance: 1 - cosine_similarity
        // So we compute 1 - distance to get similarity
        $results = DB::select("
            SELECT
                embeddable_id as candidate_id,
                1 - (embedding <=> (SELECT embedding FROM embeddings WHERE id = ?)) as similarity
            FROM embeddings
            WHERE embeddable_type = ?
              AND id != ?
            ORDER BY embedding <=> (SELECT embedding FROM embeddings WHERE id = ?)
            LIMIT ?
        ", [
            $jobEmbedding->id,
            Candidates::class,
            $jobEmbedding->id,
            $jobEmbedding->id,
            $limit,
        ]);

        return collect($results)
            ->filter(fn ($row) => $row->similarity >= $minSimilarity);
    }

    /**
     * Find the most similar jobs for a given candidate using vector cosine similarity.
     *
     * @return Collection<int, array{job_opening_id: int, similarity: float}>
     */
    public function findSimilarJobs(Candidates $candidate, int $limit = 20, float $minSimilarity = 0.5): Collection
    {
        $candidateEmbedding = Embedding::where('embeddable_type', Candidates::class)
            ->where('embeddable_id', $candidate->id)
            ->first();

        if (! $candidateEmbedding) {
            $candidateEmbedding = $this->embedCandidate($candidate);
        }

        if (! $candidateEmbedding) {
            return collect();
        }

        $results = DB::select("
            SELECT
                embeddable_id as job_opening_id,
                1 - (embedding <=> (SELECT embedding FROM embeddings WHERE id = ?)) as similarity
            FROM embeddings
            WHERE embeddable_type = ?
              AND id != ?
            ORDER BY embedding <=> (SELECT embedding FROM embeddings WHERE id = ?)
            LIMIT ?
        ", [
            $candidateEmbedding->id,
            JobOpenings::class,
            $candidateEmbedding->id,
            $candidateEmbedding->id,
            $limit,
        ]);

        return collect($results)
            ->filter(fn ($row) => $row->similarity >= $minSimilarity);
    }

    /**
     * Generate embeddings for all candidates that don't have one or are outdated.
     */
    public function embedAllCandidates(): int
    {
        $count = 0;
        Candidates::chunk(50, function ($candidates) use (&$count) {
            foreach ($candidates as $candidate) {
                $text = $this->buildCandidateText($candidate);
                $hash = hash('sha256', $text);

                $existing = Embedding::where('embeddable_type', Candidates::class)
                    ->where('embeddable_id', $candidate->id)
                    ->first();

                if ($existing && $existing->content_hash === $hash) {
                    continue; // No changes, skip
                }

                if ($this->generateAndStore($candidate, $text)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Generate embeddings for all job openings that don't have one or are outdated.
     */
    public function embedAllJobOpenings(): int
    {
        $count = 0;
        JobOpenings::chunk(50, function ($jobs) use (&$count) {
            foreach ($jobs as $job) {
                $text = $this->buildJobText($job);
                $hash = hash('sha256', $text);

                $existing = Embedding::where('embeddable_type', JobOpenings::class)
                    ->where('embeddable_id', $job->id)
                    ->first();

                if ($existing && $existing->content_hash === $hash) {
                    continue;
                }

                if ($this->generateAndStore($job, $text)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Build a rich text representation of a candidate for embedding.
     */
    protected function buildCandidateText(Candidates $candidate): string
    {
        $profile = $this->candidateExtractor->buildCandidateProfile($candidate);

        $skills = collect($profile['skills'])->pluck('name')->filter()->implode(', ');

        $experience = collect($profile['experience']['details'] ?? [])
            ->map(fn ($e) => trim(($e['role'] ?? '') . ' at ' . ($e['company'] ?? '') . ' (' . ($e['duration'] ?? '') . ')'))
            ->filter()
            ->implode('. ');

        $education = collect($profile['education']['schools'] ?? [])
            ->map(fn ($s) => trim(($s['major'] ?? '') . ' at ' . ($s['name'] ?? '')))
            ->filter()
            ->implode('. ');

        $parts = array_filter([
            "Candidate: {$profile['name']}",
            "Current Role: {$profile['experience']['current_title']}",
            "Current Employer: {$profile['experience']['current_employer']}",
            "Experience: {$profile['experience']['total_years']} years",
            $skills ? "Skills: {$skills}" : null,
            $experience ? "Work History: {$experience}" : null,
            "Education: {$profile['education']['highest_qualification']}",
            $education ? "Schools: {$education}" : null,
            "Location: {$profile['location']['city']}, {$profile['location']['state']}, {$profile['location']['country']}",
            $profile['expected_salary'] ? "Expected Salary: {$profile['expected_salary']}" : null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Build a rich text representation of a job opening for embedding.
     */
    protected function buildJobText(JobOpenings $job): string
    {
        $profile = $this->jobParser->buildJobProfile($job);

        $skills = implode(', ', $profile['required_skills']);

        $parts = array_filter([
            "Job: {$profile['posting_title']}",
            $profile['description'] ? "Description: {$profile['description']}" : null,
            $profile['requirements'] ? "Requirements: {$profile['requirements']}" : null,
            $skills ? "Required Skills: {$skills}" : null,
            $profile['experience_required'] ? "Experience Required: {$profile['experience_required']}" : null,
            "Job Type: {$profile['job_type']}",
            $profile['remote'] ? 'Remote: Yes' : null,
            $profile['salary'] ? "Salary: {$profile['salary']}" : null,
            "Location: {$profile['location']['city']}, {$profile['location']['state']}, {$profile['location']['country']}",
        ]);

        return implode("\n", $parts);
    }

    /**
     * Generate an embedding vector via OpenAI and store it.
     */
    protected function generateAndStore(Model $model, string $text): ?Embedding
    {
        if (empty($this->settings->openai_api_key)) {
            Log::warning('EmbeddingService: No OpenAI API key configured, cannot generate embeddings.');

            return null;
        }

        try {
            $vector = $this->generateEmbedding($text);

            if (! $vector) {
                return null;
            }

            $hash = hash('sha256', $text);
            $vectorJson = json_encode($vector);

            // Use raw SQL because Laravel doesn't natively handle vector type
            $existing = Embedding::where('embeddable_type', get_class($model))
                ->where('embeddable_id', $model->id)
                ->first();

            if ($existing) {
                DB::statement(
                    'UPDATE embeddings SET embedding = ?::vector, content_hash = ?, source_text = ?, updated_at = NOW() WHERE id = ?',
                    [$vectorJson, $hash, $text, $existing->id]
                );

                return $existing->fresh();
            }

            DB::statement(
                'INSERT INTO embeddings (embeddable_type, embeddable_id, embedding, content_hash, source_text, created_at, updated_at) VALUES (?, ?, ?::vector, ?, ?, NOW(), NOW())',
                [get_class($model), $model->id, $vectorJson, $hash, $text]
            );

            return Embedding::where('embeddable_type', get_class($model))
                ->where('embeddable_id', $model->id)
                ->first();
        } catch (\Throwable $e) {
            Log::error("EmbeddingService: Failed to generate embedding for " . get_class($model) . "#{$model->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Call OpenAI Embeddings API.
     *
     * @return float[]|null
     */
    protected function generateEmbedding(string $text): ?array
    {
        // Truncate to ~8000 tokens (rough estimate: 4 chars per token)
        $text = mb_substr($text, 0, 30000);

        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding ?? null;
    }
}
