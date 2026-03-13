<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enable pgvector extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('embeddable_type'); // App\Models\Candidates or App\Models\JobOpenings
            $table->unsignedBigInteger('embeddable_id');
            $table->string('content_hash', 64); // SHA-256 to detect changes
            $table->text('source_text'); // original text used to generate embedding
            $table->timestamps();

            $table->unique(['embeddable_type', 'embeddable_id']);
            $table->index(['embeddable_type', 'embeddable_id']);
        });

        // Add vector column separately (Laravel Schema doesn't support vector type)
        DB::statement('ALTER TABLE embeddings ADD COLUMN embedding vector(1536)');

        // Create HNSW index for fast cosine similarity search
        DB::statement('CREATE INDEX embeddings_embedding_idx ON embeddings USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
