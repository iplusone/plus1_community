<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('station_near_stations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('station_id')->constrained('stations')->onDelete('cascade');
            $table->foreignId('near_station_id')->constrained('stations')->onDelete('cascade');

            $table->float('distance_km')->nullable();       // 距離（km）
            $table->integer('walking_minutes')->nullable(); // 徒歩時間（分）

            $table->timestamps();

            $table->unique(['station_id', 'near_station_id']); // 重複登録防止
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_near_stations');
    }
};

