<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('railway_route_station', 'pivot_order')) {
            return;
        }

        Schema::table('railway_route_station', function (Blueprint $table) {
            $table->integer('pivot_order')->nullable()->after('station_id');
        });

        if (Schema::hasColumn('railway_route_station', 'order')) {
            DB::table('railway_route_station')
                ->select(['id', 'order'])
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('railway_route_station')
                            ->where('id', $row->id)
                            ->update(['pivot_order' => $row->order]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('railway_route_station', 'pivot_order')) {
            return;
        }

        Schema::table('railway_route_station', function (Blueprint $table) {
            $table->dropColumn('pivot_order');
        });
    }
};
