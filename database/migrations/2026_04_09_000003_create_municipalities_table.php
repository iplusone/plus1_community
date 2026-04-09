<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->char('jis_code', 5)->primary();
            $table->char('pref_code', 2);
            $table->string('pref_name');
            $table->string('city_name');
            $table->string('city_kana')->nullable();
            $table->timestamps();

            $table->index(['pref_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
