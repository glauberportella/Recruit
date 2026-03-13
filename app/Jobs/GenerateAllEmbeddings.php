<?php

namespace App\Jobs;

use App\Services\AI\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAllEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(EmbeddingService $service): void
    {
        Log::info('GenerateAllEmbeddings: Starting embedding generation for all candidates and jobs...');

        $candidateCount = $service->embedAllCandidates();
        Log::info("GenerateAllEmbeddings: Generated {$candidateCount} candidate embeddings.");

        $jobCount = $service->embedAllJobOpenings();
        Log::info("GenerateAllEmbeddings: Generated {$jobCount} job opening embeddings.");

        Log::info('GenerateAllEmbeddings: Complete.');
    }
}
