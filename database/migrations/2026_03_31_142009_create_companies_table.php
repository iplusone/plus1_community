<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status', 50)->default('approved');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
