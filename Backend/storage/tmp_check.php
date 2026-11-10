<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

// 模拟 terminate 记录一条
DB::table('user_request_log')->insert([
    'user_id' => 2731, 'ip' => '61.10.240.40', 'method' => 'POST',
    'path' => '/api/getUserCardPack', 'created_at' => date('Y-m-d H:i:s'),
]);
$row = DB::table('user_request_log')->where('ip', '61.10.240.40')->first();
echo "写入验证: log_id={$row->log_id} user={$row->user_id} ip={$row->ip} path={$row->path}\n";
DB::table('user_request_log')->where('log_id', $row->log_id)->delete();
echo "测试数据已清理\n";

// 按IP关联账号的查询示例（将来排查用）
echo "\nusers 表新字段: " . implode(',', array_map(function ($c) { return $c->Field; }, array_filter(DB::select("SHOW COLUMNS FROM users"), function ($c) { return in_array($c->Field, ['register_ip', 'last_login_ip']); }))) . "\n";
