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
        Schema::create('spot_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spot_id')->constrained()->onDelete('cascade');
            $table->foreignId('station_id')->constrained()->onDelete('cascade');
            $table->decimal('distance_km', 6, 3)->nullable();
            $table->smallInteger('walking_minutes')->unsigned()->nullable();
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->unique(['spot_id', 'station_id']);
            $table->index('station_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_stations');
    }
};
