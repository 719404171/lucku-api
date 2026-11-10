<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 鑽石兌換金幣記錄：後台「用戶管理 → 金幣兌換記錄」列表資料來源。
 * 每次兌換寫一條，含消耗鑽石、獲得金幣與當時匯率。
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('diamond_coin_exchange_log')) {
            return;
        }
        Schema::create('diamond_coin_exchange_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('用戶ID');
            $table->integer('diamond_amount')->default(0)->comment('消耗鑽石');
            $table->decimal('coin_amount', 10, 2)->default(0)->comment('獲得金幣');
            $table->decimal('rate', 10, 2)->default(1)->comment('兌換比例 1鑽石=N金幣');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('user_id', 'idx_dcel_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diamond_coin_exchange_log');
    }
};
