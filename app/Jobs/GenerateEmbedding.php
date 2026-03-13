<?php

namespace App\Jobs;

use App\Models\Candidates;
use App\Models\JobOpenings;
use App\Services\AI\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
    ) {}

    public function handle(EmbeddingService $service): void
    {
        $model = $this->resolveModel();

        if (! $model) {
            Log::warning("GenerateEmbedding: Model {$this->modelClass}#{$this->modelId} not found.");

            return;
        }

        if ($model instanceof Candidates) {
            $service->embedCandidate($model);
        } elseif ($model instanceof JobOpenings) {
            $service->embedJobOpening($model);
        }
    }

    protected function resolveModel(): ?Model
    {
        if (! in_array($this->modelClass, [Candidates::class, JobOpenings::class], true)) {
            return null;
        }

        return $this->modelClass::find($this->modelId);
    }
}
