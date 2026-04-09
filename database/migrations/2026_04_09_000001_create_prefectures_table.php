<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prefectures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('kana', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->char('code', 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prefectures');
    }
};
