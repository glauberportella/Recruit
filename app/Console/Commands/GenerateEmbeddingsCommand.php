<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAllEmbeddings;
use Illuminate\Console\Command;

class GenerateEmbeddingsCommand extends Command
{
    protected $signature = 'ai:generate-embeddings {--sync : Run synchronously instead of dispatching a job}';

    protected $description = 'Generate vector embeddings for all candidates and job openings';

    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Generating embeddings synchronously...');
            GenerateAllEmbeddings::dispatchSync();
            $this->info('Done.');
        } else {
            GenerateAllEmbeddings::dispatch();
            $this->info('Embedding generation job dispatched to queue.');
        }

        return self::SUCCESS;
    }
}
