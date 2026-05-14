<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topic_documents', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('extracted_text');
        });
    }

    public function down(): void
    {
        Schema::table('topic_documents', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
