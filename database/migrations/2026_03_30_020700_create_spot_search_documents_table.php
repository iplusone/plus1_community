<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spot_search_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spot_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('spot_name');
            $table->string('prefecture', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('town', 100)->nullable();
            $table->string('full_address')->nullable();
            $table->json('genre_names')->nullable();
            $table->json('genre_paths')->nullable();
            $table->json('tag_names')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index(['is_public', 'published_at']);
            $table->index(['prefecture', 'city', 'town']);
            $table->index('view_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_search_documents');
    }
};
