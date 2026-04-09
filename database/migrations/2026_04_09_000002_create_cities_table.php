<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pref_id')->constrained('prefectures')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('kana', 100)->nullable();
            $table->string('code', 10)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('latlng_confirmed')->default(false);
            $table->string('homepage_url')->nullable();
            $table->string('gikai_url')->nullable();
            $table->timestamp('giji_checked_at')->nullable();
            $table->string('giji_presence', 50)->nullable();
            $table->string('giji_types')->nullable();
            $table->unsignedTinyInteger('giji_confidence')->nullable();
            $table->string('giji_sample_url')->nullable();
            $table->text('giji_notes')->nullable();

            $table->index(['pref_id', 'name']);
            $table->index(['code']);
            $table->index(['latlng_confirmed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
