<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class CaptchaController extends Controller
{
    /**
     * 生成算术验证码
     * GET /api/captcha/generate
     * 
     * 返回: { "token": "xxx", "question": "3 + 4 = ?" }
     * 答案存入 Cache，5 分钟过期，一次有效（用完即删）
     */
    public function generate(Request $request)
    {
        // 生成简单算术题：a op b
        $ops = ['+', '-', '×'];
        $op = $ops[array_rand($ops)];
        $a = random_int(1, 20);
        $b = random_int(1, 20);

        // 减法确保结果非负
        if ($op === '-' && $a < $b) {
            [$a, $b] = [$b, $a];
        }
        // 乘法用小数
        if ($op === '×') {
            $a = random_int(2, 9);
            $b = random_int(2, 9);
        }

        switch ($op) {
            case '+': $answer = $a + $b; break;
            case '-': $answer = $a - $b; break;
            case '×': $answer = $a * $b; break;
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = 'captcha:' . $token;

        Cache::put($cacheKey, $answer, now()->addMinutes(5));

        return response()->json([
            'status' => 200,
            'msg'    => '成功！！',
            'results' => [
                'token'    => $token,
                'question' => "{$a} {$op} {$b} = ?",
            ],
        ]);
    }

    /**
     * 校验答案（注册接口内部调用，也可单独暴露）
     * 校验成功后立即删除，防止重复使用
     * 同一 token 校验失败 3 次也作废，防暴力尝试
     */
    public static function check($token, $answer)
    {
        if (empty($token) || $answer === null || $answer === '') {
            return false;
        }
        $cacheKey = 'captcha:' . $token;
        $failKey  = 'captcha:fail:' . $token;
        $cached = Cache::get($cacheKey);
        if ($cached === null) {
            return false; // 过期或不存在
        }
        // 已失败 3 次 → 直接作废
        $failCount = (int)Cache::get($failKey, 0);
        if ($failCount >= 3) {
            Cache::forget($cacheKey);
            Cache::forget($failKey);
            return false;
        }
        // 宽松比对：字符串转数字
        $ok = (int)$cached === (int)$answer;
        if ($ok) {
            Cache::forget($cacheKey);  // 一次有效
            Cache::forget($failKey);
        } else {
            Cache::put($failKey, $failCount + 1, 300); // 失败计数跟 token 同寿命
        }
        return $ok;
    }
}
