<?php
/*
 * 维护公告 SSE 控制器
 * 公开端点（无需认证），网站/APP 通过此端点实时获取维护状态变更
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceSseController extends Controller
{
    /**
     * 获取当前维护状态（公开接口）
     */
    public function status()
    {
        $data = DB::table('maintenance_notice')->where('id', 1)->first();
        if (empty($data)) {
            return ['status' => 200, 'msg' => 'ok', 'results' => [
                'maintenance_enabled' => 0,
                'notice_content'      => '',
                'en_notice_content'   => '',
            ]];
        }
        return ['status' => 200, 'msg' => 'ok', 'results' => (array)$data];
    }

    /**
     * SSE 维护状态推送流（公开接口）
     * 网站建立连接后，每 2 秒轮询维护状态，变化时推送事件
     */
    public function stream()
    {
        $lastEnabled = (int) DB::table('maintenance_notice')->where('id', 1)->value('maintenance_enabled');

        return response()->stream(function () use ($lastEnabled) {
            @set_time_limit(30);
            ignore_user_abort(true);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $deadline = time() + 25;

            while (!connection_aborted() && time() < $deadline) {
                $current = DB::table('maintenance_notice')->where('id', 1)->first();

                if ($current) {
                    $currentEnabled = (int)$current->maintenance_enabled;
                    if ($currentEnabled !== $lastEnabled) {
                        $lastEnabled = $currentEnabled;
                        $data = json_encode([
                            'maintenance_enabled' => $currentEnabled,
                            'notice_content'      => $current->notice_content,
                            'en_notice_content'   => $current->en_notice_content,
                            'ts'                  => time(),
                        ], JSON_UNESCAPED_UNICODE);

                        echo "event: maintenance.updated\n";
                        echo "data: {$data}\n\n";
                        flush();
                    }
                }

                echo ": heartbeat " . time() . "\n\n";
                flush();

                usleep(2000000);
            }

            echo "event: close\n";
            echo "data: " . json_encode(['msg' => 'timeout']) . "\n\n";
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=utf-8',
            'Cache-Control'     => 'no-cache, no-transform',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
