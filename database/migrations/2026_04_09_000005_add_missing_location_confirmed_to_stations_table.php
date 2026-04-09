<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stations', 'location_confirmed')) {
            Schema::table('stations', function (Blueprint $table) {
                $table->boolean('location_confirmed')->default(false)->after('longitude');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stations', 'location_confirmed')) {
            Schema::table('stations', function (Blueprint $table) {
                $table->dropColumn('location_confirmed');
            });
        }
    }
};
