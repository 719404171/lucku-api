<?php
/*
 * File: BalanceSseController.php
 * 充值後餘額實時推送（SSE）
 * APP 端建立 GET /api/balance/stream 連接，控制器每 2 秒輪詢 user_balance，
 * 餘額變化時推送 balance.updated 事件，25 秒後主動斷開讓 APP 重連。
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceSseController extends Controller
{
    /**
     * SSE 餘額變更推送流
     * APP 端帶 Authorization: Bearer {token} 請求此端點
     */
    public function stream(Request $request)
    {
        $user    = Auth::guard('api')->user();
        $userId  = (int)$user->user_id;
        $lastBalance = (float) User::where('user_id', $userId)->value('user_balance');

        return response()->stream(function () use ($userId, $lastBalance) {
            @set_time_limit(30);
            ignore_user_abort(true);

            // 關閉輸出緩衝，確保 flush() 即時推送
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $deadline = time() + 25; // 硬上限 25 秒，低於 PHP max_execution_time(30)

            while (!connection_aborted() && time() < $deadline) {
                $currentBalance = (float) User::where('user_id', $userId)->value('user_balance');

                if ($currentBalance != $lastBalance) {
                    $lastBalance = $currentBalance;
                    $data = json_encode([
                        'balance' => $currentBalance,
                        'ts'      => time(),
                    ], JSON_UNESCAPED_UNICODE);

                    echo "event: balance.updated\n";
                    echo "data: {$data}\n\n";
                    flush();
                }

                // 心跳註釋行，保持連接活躍
                echo ": heartbeat " . time() . "\n\n";
                flush();

                usleep(2000000); // 2 秒
            }

            // 主動斷開前發關閉事件
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
