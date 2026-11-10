<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * 郵件發送服務
 * - 多 SMTP 帳號輪詢（round-robin）
 * - 失敗自動切換 + 重試（總共最多 2 次）
 * - 每次發送寫入 email_send_log
 */
class MailService
{
    /**
     * 發送郵件（輪詢 + 重試 + 記錄）
     *
     * @param string $view      Blade 視圖名稱，如 'emails.verifyCode'
     * @param array  $data      傳給視圖的數據
     * @param array  $options   [
     *                           'to'      => 收件人郵箱
     *                           'subject' => 郵件標題
     *                           'from'    => 發件人地址（可選，默認讀 mail.php from）
     *                           'from_name' => 發件人名稱（可選）
     *                         ]
     * @return array            ['success' => bool, 'mailer' => string, 'attempts' => int, 'error' => string|null]
     */
    public static function send($view, array $data, array $options): array
    {
        $to      = $options['to']      ?? '';
        $subject = $options['subject'] ?? '系統通知';
        $from    = $options['from']    ?? config('mail.from.address');
        $fromName = $options['from_name'] ?? config('mail.from.name', 'Lucku');

        if (empty($to)) {
            return ['success' => false, 'mailer' => '', 'attempts' => 0, 'error' => '收件人為空'];
        }

        // 可用的 mailer 列表（至少要有一個；第二個沒配置就只用第一個）
        $mailers = ['smtp'];
        if (!empty(config('mail.mailers.smtp2.username'))) {
            $mailers[] = 'smtp2';
        }

        // 輪詢起點：用 Cache 記錄上次用的 index，每次 +1
        $rotateKey = 'mail_round_robin_index';
        $startIdx  = (int)Cache::get($rotateKey, 0);

        $maxAttempts = 2;          // 最多發 2 次
        $usedMailers = [];          // 記錄這次用了哪些 mailer（避免重複用同一個）
        $lastError   = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // 按 round-robin 順序找下一個沒用過的 mailer
            $mailerIdx = ($startIdx + $attempt - 1) % count($mailers);
            $mailer    = $mailers[$mailerIdx];

            // 如果所有 mailer 都用過了，就輪回第一個（不太可能，因為 maxAttempts=2 而 mailers>=2）
            if (in_array($mailer, $usedMailers) && count($usedMailers) < count($mailers)) {
                // 找一個沒用過的
                foreach ($mailers as $m) {
                    if (!in_array($m, $usedMailers)) {
                        $mailer = $m;
                        break;
                    }
                }
            }
            $usedMailers[] = $mailer;

            // 寫入發送中記錄
            $logId = DB::table('email_send_log')->insertGetId([
                'to_email'    => $to,
                'subject'     => $subject,
                'mailer'      => $mailer,
                'attempt'     => $attempt,
                'status'      => 0,
                'error_msg'   => null,
                'duration_ms' => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $startTime = microtime(true);

            try {
                Mail::mailer($mailer)->send($view, $data, function ($message) use ($to, $subject, $from, $fromName) {
                    $message->to($to)
                        ->from($from, $fromName)
                        ->replyTo($from, $fromName)
                        ->subject($subject);

                    // 反垃圾郵件關鍵 Header
                    $message->getHeaders()
                        ->addTextHeader('X-Mailer', 'PHP/Laravel')
                        ->addTextHeader('X-Priority', '3')
                        ->addTextHeader('Precedence', 'bulk');
                });

                $duration = (int)((microtime(true) - $startTime) * 1000);

                // 成功：更新記錄
                DB::table('email_send_log')->where('log_id', $logId)->update([
                    'status'      => 1,
                    'duration_ms' => $duration,
                    'updated_at'  => now(),
                ]);

                // 更新輪詢指針
                Cache::put($rotateKey, ($mailerIdx + 1) % count($mailers), now()->addDays(30));

                return [
                    'success'  => true,
                    'mailer'   => $mailer,
                    'attempts' => $attempt,
                    'error'    => null,
                ];
            } catch (\Throwable $e) {
                $duration = (int)((microtime(true) - $startTime) * 1000);
                $lastError = $e->getMessage();

                // 失敗：更新記錄
                DB::table('email_send_log')->where('log_id', $logId)->update([
                    'status'      => 2,
                    'error_msg'   => mb_substr($lastError, 0, 500),
                    'duration_ms' => $duration,
                    'updated_at'  => now(),
                ]);

                \Log::warning("MailService 發送失敗 (attempt {$attempt}/{$maxAttempts})", [
                    'mailer' => $mailer,
                    'to'     => $to,
                    'error'  => $lastError,
                ]);

                // 如果還有 mailer 沒試過，就換下一個重試
                // 如果所有 mailer 都試過了，且還有 attempt 次數，就輪回第一個
                continue;
            }
        }

        return [
            'success'  => false,
            'mailer'   => implode(',', $usedMailers),
            'attempts' => count($usedMailers),
            'error'    => $lastError,
        ];
    }

    /**
     * 響應返回後再發（不阻塞接口）
     */
    public static function sendAfterResponse($view, array $data, array $options): void
    {
        app()->terminating(function () use ($view, $data, $options) {
            self::send($view, $data, $options);
        });
    }
}
