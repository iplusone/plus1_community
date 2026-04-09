<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muni_finance_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->char('jis_code', 5);
            $table->decimal('fiscal_power_index', 6, 3)->nullable();
            $table->boolean('is_three_year_avg')->default(false);
            $table->bigInteger('basic_fiscal_need')->nullable();
            $table->bigInteger('standard_tax_revenue')->nullable();
            $table->bigInteger('local_allocation_tax')->nullable();
            $table->boolean('is_kofu')->nullable();
            $table->json('source_meta')->nullable();
            $table->timestamps();

            $table->unique(['year', 'jis_code', 'is_three_year_avg']);
            $table->foreign('jis_code')->references('jis_code')->on('municipalities')->cascadeOnDelete();
            $table->index(['year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muni_finance_stats');
    }
};
