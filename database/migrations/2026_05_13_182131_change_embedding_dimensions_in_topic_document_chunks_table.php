<?php

use App\Enums\DocumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The embedding column was dropped by a failed earlier run; recreate it at 768 dimensions.
        DB::statement('ALTER TABLE topic_document_chunks ADD COLUMN IF NOT EXISTS embedding vector(768)');
        DB::statement('CREATE INDEX IF NOT EXISTS topic_document_chunks_embedding_index ON topic_document_chunks USING hnsw (embedding vector_cosine_ops)');

        // Reset any documents that lost their chunks so they can be reprocessed.
        DB::table('topic_documents')
            ->whereNotIn('status', [DocumentStatus::Pending->value, DocumentStatus::Processing->value])
            ->update(['status' => DocumentStatus::Pending->value, 'chunk_count' => 0]);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS topic_document_chunks_embedding_index');
        DB::statement('ALTER TABLE topic_document_chunks DROP COLUMN IF EXISTS embedding');
    }
};
