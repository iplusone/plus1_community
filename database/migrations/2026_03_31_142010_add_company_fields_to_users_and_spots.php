<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            $table->index(['company_id', 'created_by_user_id']);
        });

        Schema::table('spots', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['company_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('spots', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'parent_id']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'created_by_user_id']);
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
