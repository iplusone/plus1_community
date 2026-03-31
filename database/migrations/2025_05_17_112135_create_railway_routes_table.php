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
        Schema::create('railway_routes', function (Blueprint $table) {
            $table->id();
            $table->string('line_name');
            $table->string('operator_name')->nullable();
            $table->string('pref_codes')->nullable(); // カンマ区切りで複数都道府県可
            $table->text('geometry')->nullable();     // マージされたGeoJSON（任意）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('railway_routes');
    }
};
