<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 查有多少 indent_type=0 的合并订单
echo "=== indent_type=0 merge orders for user 4 ===\n";
$merges = \Illuminate\Support\Facades\DB::table('order')->where('user_id', 4)->where('indent_type', 0)->get(['order_id','order_no','payment_status','order_remark','created_at']);
echo "count: " . count($merges) . "\n";
foreach ($merges as $m) echo "  oid={$m->order_id} remark={$m->order_remark} created={$m->created_at}\n";

// 完整跑 API 看结果
echo "\n=== API getOrderListApi result ===\n";
$api = new \App\Models\Order();
$r = $api->getOrderListApi(['page'=>1,'limit'=>10,'user_id'=>4]);
$data = $r['results']['data'] ?? [];
echo "total orders returned: " . count($data) . "\n";
foreach($data as $o) {
    $od = count($o['order_details'] ?? []);
    $cod = count($o['card_order_details'] ?? []);
    echo "oid={$o['order_id']} t={$o['indent_type']} od=$od cod=$cod";
    if ($od > 0) echo " [{$o['order_details'][0]['product_name']}]";
    echo "\n";
}

// 验证 indent_type=0 确实被排除
$ids = array_column($data, 'order_id');
echo "\n=== indent_type=0 excluded? ===\n";
$hasZero = false;
foreach($data as $o) if ($o['indent_type'] == 0) $hasZero = true;
echo ($hasZero ? "FAIL: 0 found!" : "OK: 0 excluded") . "\n";

// 查 oid=1602 为什么没 details
echo "\n=== oid=1602 investigation ===\n";
$d1602 = \Illuminate\Support\Facades\DB::table('order_details')->where('order_id', 1602)->count();
echo "order_details where order_id=1602: $d1602\n";
// 查 1602 的 details 是不是被搬到合并订单了
$mergeIds = $merges->pluck('order_id')->toArray();
if (!empty($mergeIds)) {
    $moved = \Illuminate\Support\Facades\DB::table('order_details')
        ->whereIn('order_id', $mergeIds)
        ->get(['details_id','order_id','product_name']);
    echo "details in merge orders: " . count($moved) . "\n";
}
