<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    private function safeIndex(string $table, \Closure $definition): void
    {
        try {
            Schema::table($table, $definition);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // 已存在同名索引時忽略（MySQL 1061 Duplicate key name）
            if (strpos($msg, 'Duplicate key name') !== false) {
                return;
            }
            // 表不存在時跳過並提示（SQLSTATE[42S02]），避免整批索引中斷
            if (strpos($msg, '42S02') !== false || stripos($msg, "doesn't exist") !== false) {
                echo "\n[安全索引] 表 {$table} 不存在，已跳過\n";
                return;
            }
            throw $e;
        }
    }

    private function safeRawIndex(string $table, string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate key name') !== false) {
                return;
            }
            if (strpos($msg, '42S02') !== false || stripos($msg, "doesn't exist") !== false) {
                echo "\n[安全索引] 表 {$table} 不存在，已跳過\n";
                return;
            }
            throw $e;
        }
    }

    public function up(): void
    {
        $this->safeIndex('order', function (Blueprint $table) {
            $table->index(['user_id', 'payment_status'], 'idx_order_user_status');
        });
        $this->safeIndex('order_details', function (Blueprint $table) {
            $table->index('order_id', 'idx_od_order');
        });
        $this->safeIndex('order_logistics', function (Blueprint $table) {
            $table->index('order_id', 'idx_ol_order');
        });
        $this->safeIndex('card_order', function (Blueprint $table) {
            $table->index('user_id', 'idx_co_user');
        });
        $this->safeIndex('card_order_details', function (Blueprint $table) {
            $table->index('card_order_id', 'idx_cod_order');
        });
        $this->safeIndex('card', function (Blueprint $table) {
            $table->index(['card_sort_id', 'card_status', 'card_stock'], 'idx_card_sort_status_stock');
        });
        $this->safeIndex('draw_session', function (Blueprint $table) {
            $table->index(['card_sort_id', 'status', 'expires_at'], 'idx_ds_sort_status_expiry');
        });
        $this->safeIndex('user_wallet_record', function (Blueprint $table) {
            $table->index('user_id', 'idx_uw_user');
        });
        $this->safeIndex('user_integral_record', function (Blueprint $table) {
            $table->index('user_id', 'idx_ui_user');
        });
        $this->safeIndex('user_cart', function (Blueprint $table) {
            $table->index('user_id', 'idx_uc_user');
        });
        $this->safeIndex('user_address', function (Blueprint $table) {
            $table->index('user_id', 'idx_ua_user');
        });
        $this->safeIndex('user_collect', function (Blueprint $table) {
            $table->index('user_id', 'idx_ucoll_user');
        });
        $this->safeIndex('system_info', function (Blueprint $table) {
            $table->index(['user_id', 'system_type'], 'idx_si_user_type');
        });
        // admins.token 為 varchar(2048) utf8，全列建索引會超長，改用前綴索引 token(255)
        $this->safeRawIndex('admins', 'ALTER TABLE `admins` ADD INDEX `idx_admins_token` (`token`(255))');
        $this->safeIndex('oauth_access_tokens', function (Blueprint $table) {
            $table->index('user_id', 'idx_oat_user');
        });
        $this->safeIndex('product', function (Blueprint $table) {
            $table->index(['product_sort_id', 'product_status'], 'idx_prod_sort_status');
        });
    }

    public function down(): void
    {
        $tables = [
            'order' => ['idx_order_user_status'],
            'order_details' => ['idx_od_order'],
            'order_logistics' => ['idx_ol_order'],
            'card_order' => ['idx_co_user'],
            'card_order_details' => ['idx_cod_order'],
            'card' => ['idx_card_sort_status_stock'],
            'draw_session' => ['idx_ds_sort_status_expiry'],
            'user_wallet_record' => ['idx_uw_user'],
            'user_integral_record' => ['idx_ui_user'],
            'user_cart' => ['idx_uc_user'],
            'user_address' => ['idx_ua_user'],
            'user_collect' => ['idx_ucoll_user'],
            'system_info' => ['idx_si_user_type'],
            'admins' => ['idx_admins_token'],
            'oauth_access_tokens' => ['idx_oat_user'],
            'product' => ['idx_prod_sort_status'],
        ];
        foreach ($tables as $table => $names) {
            foreach ($names as $name) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                } catch (\Throwable $e) {
                    // 索引不存在時忽略
                }
            }
        }
    }
};
