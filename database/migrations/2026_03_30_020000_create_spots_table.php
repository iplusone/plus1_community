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
        Schema::create('spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('spots')->nullOnDelete();
            $table->unsignedTinyInteger('depth')->default(1);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('postal_code', 20)->nullable();
            $table->string('prefecture', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('town', 100)->nullable();
            $table->string('address_line')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('description')->nullable();
            $table->text('features')->nullable();
            $table->text('access_text')->nullable();
            $table->text('business_hours_text')->nullable();
            $table->text('holiday_text')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_public', 'published_at']);
            $table->index(['prefecture', 'city', 'town']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spots');
    }
};
