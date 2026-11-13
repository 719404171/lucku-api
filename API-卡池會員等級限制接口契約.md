# 卡池會員等級限制接口契約

> 適用：後台可在每個卡池配置「允許抽卡的會員等級 + 每等級專屬可抽張數」，
> APP 端根據返回的等級限制字段，在卡池列表、卡池詳情頁展示差異化的抽卡按鈕與說明。

---

## 0. 通用約定

- 全部為 `POST`，請求體 `application/json` 或 `form-data` 均可
- 登錄態接口需在 Header 帶 `Authorization: Bearer {access_token}`
- 統一響應結構：

```json
{
  "status": 200,
  "msg": "獲取列表成功 ！！",
  "results": { ... }
}
```

- 狀態碼：`200` 成功、`500` 業務失敗、`501` 參數錯誤、`405` 未登錄、`400` 無操作權限

---

## 1. 獲取當前用戶會員檔位

`POST /api/getUsersInfo`（**需登錄**）

**請求參數**：無（用戶身份從 Token 解析）

**響應（results 內）**：

| 字段 | 類型 | 說明 |
|---|---|---|
| user_integral_all | int | 累積積分（會員等級的判定依據） |
| memberGrade | int | 當前會員等級 ID（即 `user_member.member_id`），未定級 = 0 |
| member_name | string | 當前會員等級名稱（後端按 APP 當前語言返回） |
| member_start | int | 當前等級積分下限（含） |
| member_end | int | 當前等級積分上限（含） |
| next_name | string | 下一等級名稱（用於升級引導） |
| login_bonus_coin | int | 每日簽到金币獎勵（該等級專屬） |
| login_bonus_diamond | int | 每日簽到鑽石獎勵（該等級專屬） |

**未定級用戶**（積分 < 最低檔起點）：
- `memberGrade = 0`、`member_name = 未定級 / Unranked`
- `member_end = 最低檔 member_start - 1`（例如最低檔起點是 800，則 `member_end = 799`）

**響應示例**（已分級用戶）：

```json
{
  "status": 200,
  "msg": "獲取列表成功 ！！",
  "results": {
    "user_id": 1001,
    "user_integral_all": 8520,
    "memberGrade": 3,
    "member_name": "明月",
    "member_start": 6001,
    "member_end": 10000,
    "next_name": "飛鳥",
    "login_bonus_coin": 50,
    "login_bonus_diamond": 1
  }
}
```

---

## 2. 獲取全部會員等級列表

`POST /api/getMemberApi`（**無需登錄**，可提前緩存）

**請求參數**：無

**響應 results 為數組**，每項結構：

| 字段 | 類型 | 說明 |
|---|---|---|
| member_id | int | 等級 ID |
| member_name | string | 等級中文名稱 |
| en_member_name | string | 等級英文名稱 |
| member_start | int | 積分下限（含） |
| member_end | int | 積分上限（含） |
| member_img | string | 等級圖標 URL |
| member_status | int | 1 = 啟用（正常不會返回 -1 已刪除） |
| login_bonus_coin | int | 該等級簽到金币 |
| login_bonus_diamond | int | 該等級簽到鑽石 |

> 調用一次後可緩存到本地；升級引導文案可在本地計算：
> `差距 = max(下一档 member_start - user_integral_all, 0)`

**響應示例**：

```json
{
  "status": 200,
  "msg": "獲取列表成功 ！！",
  "results": [
    {
      "member_id": 1,
      "member_name": "初心",
      "en_member_name": "Beginner",
      "member_start": 0,
      "member_end": 6000,
      "member_img": "https://...",
      "member_status": 1,
      "login_bonus_coin": 10,
      "login_bonus_diamond": 0
    },
    {
      "member_id": 3,
      "member_name": "明月",
      "en_member_name": "Moon",
      "member_start": 6001,
      "member_end": 10000,
      "member_img": "https://...",
      "member_status": 1,
      "login_bonus_coin": 50,
      "login_bonus_diamond": 1
    },
    {
      "member_id": 5,
      "member_name": "飛鳥",
      "en_member_name": "Feiniao",
      "member_start": 10001,
      "member_end": 20000,
      "member_img": "https://...",
      "member_status": 1,
      "login_bonus_coin": 100,
      "login_bonus_diamond": 3
    }
  ]
}
```

---

## 3. 卡池列表接口的等級字段

`POST /api/getCardSort`（APP 首頁 / 卡池列表頁）

**請求參數**

| 參數 | 類型 | 必填 | 說明 |
|---|---|---|---|
| page | int | 是 | 頁碼，從 1 開始 |
| limit | int | 是 | 每頁數量 |

**每個卡池 `data[i]` 內都帶完整等級限制**，字段結構與第 4 節卡池詳情完全一致：

| 字段 | 類型 | 說明 |
|---|---|---|
| member_level_limits | array | 允許抽卡的等級列表（每個等級一行），結構見下方 |
| member_level_ok | int | 當前用戶檔位是否命中該池允許等級，`1` 可抽 / `0` 不可抽 |
| member_level_draw_limit | int | 當前用戶所命中等級在本池的專屬上限，`0` = 該等級不額外限制 |
| effective_draw_limit | int | 實際生效的每用戶總上限 = `min(卡池全局上限, 當前等級上限)`，`0` = 不限 |
| draw_remaining | int | 剩餘可抽張數（已按 `effective_draw_limit` 計算），`-1` = 不限 |
| max_draw_count | int | **本次單次可選最大張數**（後端已綜合所有限制算好），APP 直接用 |

`member_level_limits` 元素結構：

```json
[
  {
    "member_id": 3,
    "name": "明月",
    "score_range": [6001, 10000],
    "member_end": 10000,
    "draw_limit": 1
  },
  {
    "member_id": 5,
    "name": "飛鳥",
    "score_range": [10001, 20000],
    "member_end": 20000,
    "draw_limit": 2
  }
]
```

| 字段 | 類型 | 說明 |
|---|---|---|
| member_id | int | 等級 ID |
| name | string | 等級名稱（已按 APP 語言返回） |
| score_range | array | 該等級的積分區間 `[start, end]` |
| member_end | int | 該等級的 `member_end`（比較基準） |
| draw_limit | int | 該等級用戶在本卡池的累計可抽張數，`0` = 該等級不額外限制 |

**關鍵語義**

- **空陣列 `[]`** = 所有等級均可抽，不額外限制（此時 `member_level_ok` 固定為 `1`）
- **非空** = 只有列表內等級可以抽；每個等級可配獨立的 `draw_limit`
- `member_level_ok = 0` 時，`max_draw_count` 自動歸零，抽卡按鈕應禁用
- 未登錄時視為未定級（`user_integral_all = 0`），若卡池配置了等級限制 → `member_level_ok = 0`、`max_draw_count = 0`

---

## 4. 卡池詳情接口的等級字段

`POST /api/getCardsBySort`（進入卡池詳情頁）

**請求參數**

| 參數 | 類型 | 必填 | 說明 |
|---|---|---|---|
| card_sort_id | int | 是 | 卡池 ID |
| page | int | 是 | 頁碼，從 1 開始 |
| limit | int | 是 | 每頁數量 |

**返回的等級相關字段與第 3 節完全相同**，額外多返回以下兼容字段：

| 字段 | 類型 | 說明 |
|---|---|---|
| draw_limit_per_user | int | 卡池全局上限（後台獨立配置，不區分等級），`0` = 不限 |
| member_level_limit | int | 兼容舊字段：列表第一個等級 ID，`0` = 不限 |
| member_level_limit_name | string\|null | 兼容舊字段：等級名稱，多個以 `/` 拼接 |
| member_level_limit_score_range | array\|null | 兼容舊字段：僅單等級時返回 `[start, end]` |

---

## 5. 抽卡接口的等級校驗

`POST /api/confirmDrawOrder`（**需登錄**）

**後端硬校驗**，不符直接返回業務錯誤：

| 場景 | msg |
|---|---|
| 會員等級不符 | 該卡池僅限指定會員等級抽取，您當前的會員等級不符，請查看卡池說明！ |

APP 端拿到此 `msg` 後，可在彈窗裡額外展示升級引導文案（積分差距可在本地計算）。

---

## 6. 接口字段一覽（等級相關）

| 接口 | 是否需要登錄 | 等級相關字段 |
|---|---|---|
| `getUsersInfo` | 是 | `memberGrade` / `member_name` / `member_start` / `member_end` / `next_name` / `user_integral_all` |
| `getMemberApi` | 否 | 全部等級的 `member_id` / `member_name` / `en_member_name` / `member_start` / `member_end` / `member_img` |
| `getCardSort` | 否（登錄後更精確） | `member_level_limits` / `member_level_ok` / `member_level_draw_limit` / `effective_draw_limit` / `draw_remaining` / `max_draw_count` |
| `getCardsBySort` | 否（登錄後更精確） | 同上 + `draw_limit_per_user` + 兼容字段 |
| `confirmDrawOrder` | 是 | 後端硬校驗，不符直接返回 `msg` |

---

## 7. APP 端展示建議

### 7.1 卡池列表（首頁）

```
[卡池封面]
卡池名稱
「明月 1張 / 飛鳥 2張」   ← 讀 member_level_limits 陣列拼接，draw_limit=0 時只顯示等級名
剩餘 XX 張
[抽卡] 或 [等級不符]     ← member_level_ok==0 時禁用按鈕
```

### 7.2 卡池詳情頁

```
頂部橫幅：
  您當前是「明月」會員，本池可抽 1 張，已抽 0 張
  （組合：getUsersInfo.member_name + effective_draw_limit + draw_count）

完整等級表（後台配了幾個等級就顯示幾行）：
  ┌──────────┬──────────────┬──────────┐
  │ 等級     │ 積分範圍     │ 可抽張數 │
  ├──────────┼──────────────┼──────────┤
  │ 初心     │ 0 ~ 6000     │ 不可抽   │  ← 不在 member_level_limits 裡
  │ 明月 ◀我 │ 6001 ~ 10000 │ 1 張     │  ← memberGrade 命中的一行加高亮
  │ 飛鳥     │ 10001 ~ 20000│ 2 張     │
  └──────────┴──────────────┴──────────┘

抽卡按鈕上方：
  可用：剩餘 1 張 / 今日剩餘 X 張 / 每單最多 N 張
  按鈕輸入範圍：1 ≤ N ≤ max_draw_count
```

### 7.3 抽卡結果提示

- `member_level_ok = 0` 時，後端 `confirmDrawOrder` 直接拒絕並返回 msg
- APP 捕獲 msg 後可彈窗展示升級引導：
  > 您當前是「初心」會員，該卡池僅限「明月」及以上會員抽取。
  > 距離「明月」還差 **6001** 積分，每日簽到可獲得 **10** 積分。

---

## 8. 兼容性說明

- 所有等級相關字段是增量返回，舊版 APP 忽略即可，不影響原有邏輯
- `member_level_limits` 為空陣列時等價於「不限制等級」，APP 可直接隱藏等級說明
- 數據庫新列不存在時，後端自動回退為「不限制等級」，不會導致接口報錯
- 舊字段 `member_level_limit`（單等級）與新字段 `member_level_limits`（多等級）同時存在時，以新字段為準，舊字段僅做兼容展示
