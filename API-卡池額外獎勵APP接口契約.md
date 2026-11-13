# 卡池額外獎勵 APP 端接口契約

> 適用：後台可在每個卡池配置「每 N 張發一張額外獎勵卡」（卡池全局計數，不是用戶個數），
> APP 端通過以下 4 個接口讀取獎勵規則、接收獎勵卡，並在卡池列表/詳情/抽卡結果頁做差異化展示。

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

- 狀態碼：`200` 成功、`500` 業務失敗、`501` 參數錯誤、`405` 未登錄

### 獎勵卡字段含義

| 字段 | 類型 | 說明 |
|---|---|---|
| is_bonus | int | `1` 表示這張是額外獎勵卡，`0` 或不存在 = 正常抽中的卡 |
| bonus_position | bigint | 這張獎勵卡是第幾張觸發點（例如規則 N=10，獎勵卡在第 30 張觸發 → `bonus_position=30`） |
| bonus_rule_id | bigint | 觸發本張獎勵的後台規則 ID（僅用於後續排查，APP 一般不用關心） |

### 觸發時機（APP 無需自行計算）

- 後端在每次抽卡（`confirmDrawOrder`）的事務內自動判定：
  1. 原子自增卡池全局計數器 `drawn_total`
  2. 計算本次跨越了哪些 N 的倍數（如 N=10、抽 100 張，跨越 10、20...100 共 10 個觸發點）
  3. 每個觸發點對應的獎勵卡扣庫存、寫發放記錄（保證同一觸發點全局只發一次）
  4. 獎勵卡免費、正常建單進背包，在返回的 `cards` 數組裡標 `is_bonus=1`
- **獎勵卡庫存不足 / 已下架時跳過**，不影響本次付費抽卡結果

---

## 1. 卡池列表接口

`POST /api/getCardSort`（APP 首頁 / 卡池列表頁，**無需登錄**）

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|---|---|---|---|
| page | int | 是 | 頁碼，從 1 開始 |
| limit | int | 是 | 每頁數量 |
| user_id | int | 否 | 登錄後傳入，後端會按當前用戶檔位計算剩餘可抽張數 |

### 每個卡池 `data[i]` 額外獎勵相關字段

| 字段 | 類型 | 說明 |
|---|---|---|
| bonus_rules | array | 本池已啟用的額外獎勵規則列表（按 trigger_at 升序） |
| drawn_total | int | 本池全局已抽張數（獎勵卡不算在內，是正常卡的累計） |

`bonus_rules` 元素結構：

```json
{
  "trigger_at": 10,
  "card_id": 123,
  "card_name": "SSR 鳳凰",
  "card_img": "https://cdn.xxx.com/cards/phoenix.png",
  "card_tag": "典藏"
}
```

| 字段 | 類型 | 說明 |
|---|---|---|
| trigger_at | int | 每 N 張發一次（第 N、2N、3N... 張都發） |
| card_id | int | 獎勵卡牌 ID |
| card_name | string | 獎勵卡名稱（已按 APP 語言返回） |
| card_img | string | 獎勵卡圖片 URL |
| card_tag | string | 獎勵卡標籤（如 SSR、典藏等） |

**空陣列 `[]`** = 本池未配置任何額外獎勵，APP 可隱藏獎勵相關 UI。

**響應示例**（單個卡池）：

```json
{
  "status": 200,
  "msg": "獲取列表成功 ！！",
  "results": {
    "data": [
      {
        "card_sort_id": 5,
        "card_sort_name": "夏日祭典",
        "card_sort_img": "https://cdn.xxx.com/sorts/summer.png",
        "drawn_count": 3280,
        "undrawn_count": 720,
        "drawn_total": 3280,
        "bonus_rules": [
          {
            "trigger_at": 10,
            "card_id": 101,
            "card_name": "SSR 鳳凰",
            "card_img": "https://cdn.xxx.com/cards/phoenix.png",
            "card_tag": "典藏"
          },
          {
            "trigger_at": 50,
            "card_id": 205,
            "card_name": "限定 夏日女神",
            "card_img": "https://cdn.xxx.com/cards/goddess.png",
            "card_tag": "限定"
          }
        ]
      }
    ]
  }
}
```

---

## 2. 卡池詳情接口

`POST /api/getCardsBySort`（進入卡池詳情頁，**無需登錄**）

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|---|---|---|---|
| card_sort_id | int | 是 | 卡池 ID |
| page | int | 是 | 頁碼（用於分頁讀取卡池內卡片） |
| limit | int | 是 | 每頁數量 |

### 額外獎勵相關字段

**與第 1 節卡池列表完全相同**，在 `results` 頂層返回（不是在 `data` 內）：

| 字段 | 類型 | 說明 |
|---|---|---|
| bonus_rules | array | 規則列表，結構同第 1 節 |
| drawn_total | int | 本池全局已抽張數 |

**響應示例**（頂層字段）：

```json
{
  "status": 200,
  "msg": "獲取列表成功 ！！",
  "results": {
    "card_sort_id": 5,
    "card_sort_name": "夏日祭典",
    "card_draw_price": 120,
    "bonus_rules": [
      {
        "trigger_at": 10,
        "card_id": 101,
        "card_name": "SSR 鳳凰",
        "card_img": "https://cdn.xxx.com/cards/phoenix.png",
        "card_tag": "典藏"
      }
    ],
    "drawn_total": 3280,
    "count": 200,
    "tags": ["SSR", "SR", "R", "預設"],
    "data": {
      "SSR": [ ... ],
      "SR": [ ... ]
    }
  }
}
```

---

## 3. 抽卡接口

`POST /api/confirmDrawOrder`（**需登錄**）

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|---|---|---|---|
| card_sort_id | int | 是 | 卡池 ID |
| count | int | 是 | 本次抽卡張數（1 = 單抽、10 = 十連、100 = 百連、或自訂 1 ~ max_draw_count） |
| user_id | int | 是 | 當前用戶 ID |

### 響應

**獎勵卡走正常的 `cards` 數組返回，但帶 `is_bonus=1` 標記**，APP 需據此做差異化展示：

```json
{
  "status": 200,
  "msg": "抽卡成功！",
  "results": {
    "task_id": 58921,
    "cards": [
      {
        "card_id": 101,
        "card_name": "SSR 鳳凰",
        "card_img": "https://cdn.xxx.com/cards/phoenix.png",
        "card_tag": "典藏",
        "card_order_details_id": 88901,
        "is_bonus": 1,
        "bonus_position": 3290,
        "bonus_rule_id": 7
      },
      {
        "card_id": 45,
        "card_name": "SR 海浪",
        "card_img": "https://cdn.xxx.com/cards/wave.png",
        "card_tag": "SR",
        "card_order_details_id": 88902
      },
      {
        "card_id": 46,
        "card_name": "R 陽光",
        "card_img": "https://cdn.xxx.com/cards/sun.png",
        "card_tag": "R",
        "card_order_details_id": 88903
      }
    ],
    "order_ids": [ ... ],
    "total_cost": 1200
  }
}
```

關鍵點：
- `cards` 數組包含本次付費抽的卡 + 額外獎勵卡
- **獎勵卡免費**，`total_cost` 只計算本次付費張數，與獎勵卡無關
- 獎勵卡**正常建單進背包**，與付費卡有同樣的 `card_order_details_id`
- 獎勵卡的位置不固定在開頭或結尾，後端按獎勵卡的 `bonus_position` 插入數組中對應順序位置

---

## 4. 抽卡結果查詢接口

`POST /api/getDrawTaskStatus`（**需登錄**）

查詢 `confirmDrawOrder` 產生的任務狀態，返回的 `cards` 與第 3 節完全一致，**獎勵卡的 `is_bonus / bonus_position / bonus_rule_id` 字段會完整保留**（因為後端在寫入 `card_draw_tasks.cards_json` 時已經包含這些字段）。

---

## 5. APP 端展示建議

### 5.1 卡池列表（首頁）角標

```
[卡池封面]
卡池名稱
「每 10 張送 SSR 鳳凰 / 每 50 張送限定夏日女神」  ← bonus_rules 陣列拼接
剩餘 720 張 | 已抽 3280 張
[抽卡]
```

拼接規則：
- 每條規則 → `每 {trigger_at} 張送 {card_name}`
- 多條用 `/ ` 或換行分隔
- 無規則（空陣列）→ 不顯示獎勵角標
- 獎勵卡的 `card_tag` 可作為輔助展示（如標籤樣式）

### 5.2 卡池詳情頁

```
頂部橫幅：
  本池全局已抽 {drawn_total} 張

獎勵規則區塊（僅當 bonus_rules 非空時顯示）：
  ┌──────────────────────────────────────┐
  │ 🎁 額外獎勵                           │
  │                                      │
  │ 每 10 張 → [圖] SSR 鳳凰              │
  │ 每 50 張 → [圖] 限定 夏日女神          │
  └──────────────────────────────────────┘

抽卡按鈕上方：
  可用：剩餘 720 張 / 每單最多 10 張
```

### 5.3 抽卡動畫 / 結果頁

- 獎勵卡（`is_bonus = 1`）應與付費卡做出**視覺差異**：
  - 金色/特別边框
  - 進場動畫（如閃光、飛入）
  - 卡面顯示「🎁 額外獎勵」角標
- `bonus_position` 可用於說明文案：「這張獎勵卡在本池第 **3290** 張時發出」（可選，後續規則迭代時可能改語義）
- 獎勵卡不參與「本次花了多少錢」的計算，UI 上無需顯示價格

### 5.4 邊界情況

| 場景 | APP 應對 |
|---|---|
| bonus_rules 空陣列 | 隱藏獎勵相關 UI |
| drawn_total = 0 | 顯示「0」即可（新卡池從零開始） |
| 獎勵卡庫存不足（後端已跳過） | 用戶無感知，僅在後台 log 記錄，APP 正常展示付費抽卡結果 |
| 一次抽 100 張、跨越多個 N | 後端逐條發，APP 結果頁按 `cards` 順序展示多張獎勵卡即可 |

---

## 6. 兼容性說明

- **所有獎勵相關字段都是增量返回**：`bonus_rules` 空陣列、`drawn_total=0` 時等價於「未配置獎勵」，舊版 APP 忽略即可
- **獎勵卡的 `is_bonus` 字段**：舊版 APP 不認識該字段，正常把獎勵卡當普通卡顯示，卡片照常進背包，業務無損
- **數據庫新表/新列不存在時**：後端自動回退（`bonus_rules=[]`、`drawn_total=0`），不會導致接口報錯
- **獎勵卡的額外標記字段（`is_bonus / bonus_position / bonus_rule_id`）**：僅在獎勵卡上存在，普通卡沒有這些字段，APP 判空即可
- **與既有「會員等級」「每用戶抽卡上限」功能獨立**：獎勵卡不消耗用戶的每用戶上限、不參與 `max_draw_count` 計算，兩者互不影響
