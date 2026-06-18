<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mlit_spots', function (Blueprint $table) {
            $table->id();

            // データセット識別
            $table->string('dataset_code', 10);           // P11, P04, A27 など（mlit_datasets.code）
            $table->string('source_id', 100);             // 元データの安定識別子（hash 等）

            // カテゴリ（mlit_datasets から非正規化して検索を高速化）
            $table->string('category', 50);               // transport / medical / education / welfare / disaster / tourism / public / industry
            $table->string('sub_category', 100)->nullable(); // bus_stop / hospital / elementary_school / welfare_facility / evacuation / roadside_station ...

            // 基本属性
            $table->string('name', 255);                  // 施設名
            $table->char('pref_code', 2)->nullable();     // 都道府県コード（例: 12）
            $table->string('admin_area_code', 10)->nullable(); // 行政区域コード（5桁市区町村コード）
            $table->string('address', 500)->nullable();   // 所在地

            // 座標（全データ必須）
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // データ種別固有の追加属性
            $table->json('attributes')->nullable();

            // 管理情報
            $table->string('source_year', 10)->nullable(); // データ年度（例: 2021）
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();

            // 同一データセット内での重複防止
            $table->unique(['dataset_code', 'source_id']);

            // 検索用インデックス
            $table->index('pref_code');
            $table->index('admin_area_code');
            $table->index(['category', 'sub_category']);
            $table->index(['latitude', 'longitude']);
            $table->index('dataset_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mlit_spots');
    }
};
