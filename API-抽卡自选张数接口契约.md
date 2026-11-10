# 抽卡「自选张数」接口契约（替代一键包池）

> 适用：APP 端卡池详情页把「一键包池」改为「输入/选择张数 N 后一次抽 N 张」。
> 后端无需改动，`/api/confirmDrawOrder` 已支持任意张数。

---

## 0. 通用约定

- 全部为 `POST`，请求体 `application/json` 或 `form-data` 均可
- 登录态接口需在 Header 带 `Authorization: Bearer {access_token}`
- 统一响应结构（注意字段名是 `status` / `msg` / `results`，不是 code/message/data）：

```json
{
  "status": 200,
  "msg": "获取列表成功 ！！",
  "results": { ... }
}
```

- 状态码：`200` 成功、`500` 业务失败、`501` 参数错误、`405` 未登录、`400` 无操作权限（封禁/注销）
- 失败时 `msg` 为可直接展示给用户的文案；`results` 可能不存在

---

## 1. 获取卡池详情（拿可抽上限）

`POST /api/getCardsBySort`

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| card_sort_id | int | 是 | 卡池 ID |
| page | int | 是 | 页码，从 1 开始（必传，否则报参数错误） |
| limit | int | 是 | 每页数量（必传） |

**响应中与「自选张数」相关的字段（均在 `results` 内）**

| 字段 | 类型 | 说明 |
|---|---|---|
| card_draw_price | float | 单抽价格（总价 = card_draw_price × N） |
| total_remaining | int | 卡池剩余可抽张数（已扣除处理中占位） |
| total_drawn | int | 卡池已抽张数 |
| total_stock | int | 卡池总库存（所有上架卡片库存之和） |
| draw_limit_per_user | int | 每用户总张数上限（后台全局配置），`0` = 不限 |
| draw_remaining | int | 每用户剩余可抽张数（已按「全局上限与会员等级上限取较小值」计算），`-1` = 不限 |
| daily_draw_limit | int | 每人每日张数上限，`0` = 不限 |
| draw_today_remaining | int | 今日剩余可抽张数，`-1` = 不限 |
| is_once_only | int | 优惠卡包标记，`1` = 仅能单抽（N 必须为 1） |
| member_level_limits | array | **允许抽卡的会员等级列表（多选），空数组 = 不限制** |
| member_level_limit | int | 兼容旧字段：列表第一个等级 ID，`0` = 不限 |
| member_level_limit_name | string\|null | 兼容旧字段：等级名称，多个时以 `/` 拼接 |
| member_level_limit_score_range | array\|null | 兼容旧字段：仅单个等级时返回 `[start, end]` |
| member_level_ok | int | 当前用户档位是否命中允许等级，`1` 可抽 / `0` 不可抽 |
| member_level_draw_limit | int | 当前用户所命中等级的专属张数上限，`0` = 该等级不额外限制 |
| effective_draw_limit | int | 实际生效的每用户总上限 = min(全局上限, 等级上限)，`0` = 不限 |
| max_draw_count | int | **本次单次可选的最大张数（后端已算好，直接使用）** |

`member_level_limits` 元素结构：

```json
[
  { "member_id": 3, "name": "明月", "score_range": [6001, 10000], "draw_limit": 1 },
  { "member_id": 5, "name": "飛鳥", "score_range": [10001, 20000], "draw_limit": 2 }
]
```

> 语义：列表为空 = 所有等级均可抽；非空时只有列表内等级可抽。
> `draw_limit` 是该等级用户在**本卡池**的累计可抽张数（`0` = 该等级不额外限制）。
> 同时配置了全局 `draw_limit_per_user` 时，取两者较小值。

**可输入张数（APP 端直接用 `max_draw_count`）**

```
maxN = results.max_draw_count   // 已综合：卡池剩余 / 生效张数上限 / 今日剩余 / 优惠卡包(1) / 单次上限
```

- `maxN = 0`：按钮置灰，提示不可抽取（库存不足、次数用完或会员等级不符）
- 输入范围 `1 ≤ N ≤ maxN`
- 旧版后端没有该字段时，APP 可回退为手动计算：
  ```
  maxN = total_remaining
  draw_remaining > 0        → min(maxN, draw_remaining)        // -1 视为不限
  draw_today_remaining > 0  → min(maxN, draw_today_remaining)
  is_once_only == 1         → min(maxN, 1)
  member_level_ok == 0      → 0
  ```

---

## 2. 抽卡（一次抽 N 张）

`POST /api/confirmDrawOrder`

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| card_sort_id | int | 是 | 卡池 ID |
| count | int | 是 | 本次抽取张数 N（正整数，即用户输入值；不再传「剩余全部」） |

> 用户身份从 Token 解析，无需传 user_id。
> 该接口挂有防重复提交中间件（3 秒内重复请求会被拦截）。

**总价**

```
total_cost = card_draw_price × count
```

**成功响应（异步建单）**

```json
{
  "status": 200,
  "msg": "获取列表成功 ！！",
  "results": {
    "status": "processing",
    "task_id": 1024,
    "order_ids": [],
    "cards": [
      {
        "card_id": 123,
        "card_sort_id": 57,
        "card_name": "卡片名称",
        "en_card_name": "Card Name",
        "card_img": "https://...",
        "card_price": "100.00",
        "card_type": 1,
        "card_tag": "一等奖",
        "card_stock": 1
      }
    ],
    "total_cost": 1000
  }
}
```

- `cards` 为本次抽中的卡片数组，长度 = count（随机/预设顺序由后端决定）
- 资金已在返回前扣除，订单由队列异步生成
- 注意：外层 `status` 是 HTTP 风格响应码（200 成功）；`results.status` 才是业务状态（`processing`）

**失败响应（常见 `msg`，业务失败 `status` 为 500，参数错误为 501，未登录为 405）**

| 场景 | msg |
|---|---|
| 参数缺失/非法 | 參數錯誤 ！！ |
| 未登录 | 用戶未登錄 ！！ |
| 抽卡入口关闭 | 抽卡功能維護中，請稍後再試 ！！ |
| 会员等级不符 | 該卡池僅限指定會員等級抽取，您當前的會員等級不符，請查看卡池說明！ |
| 优惠卡包多抽 | 該優惠卡包僅支持單次抽取！！ |
| 优惠卡包重复抽 | 該優惠卡包每個賬號僅能抽取一次！！ |
| 超出每用户总张数 | 您在該卡池的抽取張數已用完，剩餘 {remain} 張！ |
| 超出每日张数 | 您今日在該卡池的抽取張數已用完，每日0點重置，明天再來吧！ |
| 超出单次张数上限 | 單次最多可抽取 50 張，請調整抽取數量！ |
| 库存不足 | 可抽取的卡牌不足，請補充庫存 ！！ |
| 余额不足 | 錢包餘額不足，請充值或者選擇其他支付方式 !! |
| 并发提交 | 操作過於頻繁，請稍後重試 / 系統繁忙，請重試 |

> 并发抢卡导致库存被抢光时同样返回「可抽取的卡牌不足」，APP 可提示用户刷新后重试。

---

## 3. 轮询建单结果

`POST /api/getDrawTaskStatus`

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| task_id | int | 是 | 上一步返回的 task_id |

**响应**

```json
{
  "status": 200,
  "msg": "获取列表成功 ！！",
  "results": {
    "task_id": 1024,
    "status": 1,
    "cards": [ { "card_id": 123, "...": "...", "card_order_details_id": 456 } ],
    "total_cost": 1000
  }
}
```

| status | 含义 | APP 动作 |
|---|---|---|
| 0 | 建单中 | 继续轮询（建议 1s 间隔，最多约 10 次） |
| 1 | 已建单 | 停止轮询，刷新背包/订单列表，播放抽卡动画 |
| 2 | 待人工处理 | 停止轮询，提示稍后查看背包 |

---

## 4. APP 端改造要点

1. 卡池详情页去掉「一键包池」直接提交 `count = total_remaining` 的逻辑
2. 改为点击「自选张数」→ 弹窗输入 N（或步进器），实时显示 `总价 = card_draw_price × N`
3. 提交 `{ card_sort_id, count: N }`，成功后按第 3 步轮询 task_id
4. 输入上限直接用 `results.max_draw_count`；`max_draw_count = 0` 时禁用按钮
5. 其余限制（优惠卡包、会员等级、余额、单次上限）均由后端兜底，APP 只需展示 `msg`

---

## 5. 单次抽取张数上限配置（后端）

后端已支持单次张数上限，配置项在 `app_settings` 表：

```sql
-- 设置单次最多 100 张
INSERT INTO app_settings (`key`, `value`, `updated_at`)
VALUES ('max_draw_count_per_order', '100', NOW())
ON DUPLICATE KEY UPDATE `value` = '100', `updated_at` = NOW();

-- 取消限制（删除该行，或设为 0）
DELETE FROM app_settings WHERE `key` = 'max_draw_count_per_order';
```

- 未配置或值为 `0` → 不限制（保持原「一键包池」大额抽取可用）
- 配置后：`getCardsBySort` 的 `max_draw_count` 会同步收窄；`confirmDrawOrder` 超出直接返回「單次最多可抽取 N 張」
- 修改后无需重启，下一次请求即生效（该值不走配置缓存）

---

## 6. 兼容性说明

- `count` 传「卡池剩余全部张数」的旧「一键包池」调用方式继续有效，无需后端迁移
- 现有 10 连/100 连按钮逻辑不变，仍传 `count = 10 / 100`
- 新增的 `max_draw_count` 字段为增量返回，旧版 APP 忽略即可，不影响原有逻辑
