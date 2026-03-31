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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_name');
            $table->string('line_name')->nullable();
            $table->string('operator_name')->nullable();
            $table->string('year')->nullable();
            $table->string('pref_code')->nullable();
            $table->decimal('longitude', 10, 6); // 経度
            $table->decimal('latitude', 10, 6);  // 緯度
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
