<?php

// database/migrations/xxxx_xx_xx_create_railway_route_station_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRailwayRouteStationTable extends Migration
{
    public function up()
    {
        Schema::create('railway_route_station', function (Blueprint $table) {
            $table->id();
            $table->foreignId('railway_route_id')->constrained()->onDelete('cascade');
            $table->foreignId('station_id')->constrained()->onDelete('cascade');
            $table->integer('order')->nullable()->comment('駅の並び順');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('railway_route_station');
    }
}
