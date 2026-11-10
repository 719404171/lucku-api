# SSE 余额实时推送方案

## Context

充值完成后，APP 端无法及时显示更新后的余额，用户需手动刷新。现有充值流程：APP 调用 `/PaymentRecharge` → Wonder 支付 → Webhook 回调 `RechargeComplete()` 更新 `user_balance`。APP 无法感知余额变化。

方案：新增 SSE（Server-Sent Events）端点，APP 在充值期间建立连接，控制器轮询 `users.user_balance`，余额变化时推送事件。

## 改动清单

### 1. 新建 SSE 控制器
**文件**: `app/Http/Controllers/Api/BalanceSseController.php`

- `stream(Request)` 方法，使用 `response()->stream()` 返回 SSE 流式响应
- 通过 `Auth::guard('api')->user()` 获取用户 ID
- 连接建立时记录初始余额 `$lastBalance`
- 循环每 2 秒查询 `User::where('user_id', $userId)->value('user_balance')`
- 余额变化时输出 `event: balance.updated` + `data: {json}` 并刷新缓冲
- 空闲时输出心跳注释行保持连接活跃
- 连接硬上限 25 秒，到时主动断开让 APP 重连（避免 PHP `max_execution_time` 超时）
- 检测 `connection_aborted()` 提前退出
- 响应头: `Content-Type: text/event-stream`, `Cache-Control: no-cache`, `X-Accel-Buffering: no`

### 2. 注册路由
**文件**: `routes/api.php`（在充值接口块 L222 附近后添加）

```php
Route::get("/balance/stream", "Api\BalanceSseController@stream")
    ->middleware(['checkLogin', 'throttle:120,1']);
```

- GET 方法（SSE 规范要求）
- `checkLogin` 中间件鉴权（APP 通过 `Authorization: Bearer` 头传 token）
- 不挂 `prevent.duplicate`（需允许重连）
- 宽松 throttle 避免重连风暴触发限流

### 3. 不改动现有代码
- `RechargeComplete` 不改 — SSE 控制器直接轮询 DB 检测余额变化
- `CheckLogin` 不改 — 复用现有 Passport 认证
- `composer.json` 不改 — 零新依赖

## 关键文件参考
- `app/Http/Middleware/CheckLogin.php` — 认证中间件，`Auth::guard('api')->user()`
- `app/Http/Controllers/Controller.php` — 基类（SSE 控制器继承但不使用 success/error）
- `routes/api.php` L222 — 充值路由块，SSE 路由插入位置
- `app/Models/User.php` — 余额字段 `user_balance`，主键 `user_id`

## APP 端对接
1. 充值发起后打开 `GET /api/balance/stream`，带 `Authorization: Bearer {token}`
2. 解析 `event: balance.updated` 行，取 `data:` JSON 中 `balance` 字段更新 UI
3. 连接 25 秒后服务端断开，APP 自动重连
4. 离开充值/支付页时关闭 SSE 连接

## 验证
1. 本地 `php artisan serve` 启动
2. 用 token 调 `GET /api/balance/stream`，确认 SSE 连接建立并收到心跳
3. 手动 SQL 更新 `users.user_balance`，确认 SSE 推送 `balance.updated` 事件
4. 25 秒后确认连接正常断开
