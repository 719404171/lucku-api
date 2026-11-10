<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // 抽卡預設順序表：把 draw_preset.preset_data（可達 MB 級 JSON）拆成有序行，
        // 抽卡時直接 JOIN 取前 N 張在庫卡，避免讀取與解析大 JSON、避免巨型 whereIn。
        if (!Schema::hasTable('draw_preset_items')) {
            Schema::create('draw_preset_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('preset_id')->default(0);
                $table->unsignedBigInteger('card_sort_id')->default(0);
                $table->unsignedInteger('seq')->default(0)->comment('預設順序，從 1 開始');
                $table->unsignedBigInteger('card_id')->default(0);
                $table->index(['card_sort_id', 'seq'], 'idx_dpi_sort_seq');
                $table->index('card_id', 'idx_dpi_card');
                $table->unique(['preset_id', 'seq'], 'uniq_dpi_preset_seq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_preset_items');
    }
};
