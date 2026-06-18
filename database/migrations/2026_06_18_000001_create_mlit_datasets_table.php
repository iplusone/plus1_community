<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mlit_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();         // P11, P04, A27 など
            $table->string('name', 100);                  // バス停留所, 医療機関 など
            $table->string('category', 50);               // transport / medical / education / welfare / disaster / tourism / public / industry
            $table->string('license', 100)->nullable();   // CC BY 4.0 など
            $table->text('download_url')->nullable();      // 取得元 URL
            $table->string('source_year', 10)->nullable(); // データ年度（例: 2021）
            $table->timestamp('last_imported_at')->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mlit_datasets');
    }
};
