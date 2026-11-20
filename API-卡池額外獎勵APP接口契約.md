# 卡池額外獎勵 APP 端接口契約

> 適用：後台可在每個卡池配置「第 N 張發一張額外獎勵卡」（卡池全局計數，**一次性觸發**，不是每 N 張循環），
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
| bonus_position | int | 這張獎勵卡的觸發點（即後台配置的 `trigger_at`，例如 `trigger_at=30` 就只在卡池全局第 30 張時發一次） |
| bonus_rule_id | int | 觸發本張獎勵的後台規則 ID（僅用於後續排查，APP 一般不用關心） |

### 觸發時機（APP 無需自行計算）

- 後端在每次抽卡（`confirmDrawOrder`）的事務內自動判定：
  1. 原子自增卡池全局計數器 `drawn_total`（只計付費卡，獎勵卡不計入）
  2. 根據自增前後的區間 `(before, after]` 計算本次命中了哪些規則的 `trigger_at`
  3. 每個命中的規則寫 `card_sort_bonus_log`（唯一鍵 `(rule_id, trigger_position)` 保證同一觸發點全局只發一次）
  4. 獎勵卡扣庫存、寫獎勵卡記錄，與付費卡一起返回
- **獎勵卡庫存不足 / 已下架時跳過**，不影響本次付費抽卡結果
- **trigger_at 語義：單次觸發**。例如 `trigger_at=10` 只在卡池抽到第 10 張時發一次，第 20、30... 張不會自動觸發（除非後台另外配置了 `trigger_at=20`、`trigger_at=30` 的規則）

---

## 1. 卡池列表接口

`POST /api/getCardSort`（APP 首頁 / 卡池列表頁，**無需登錄**）

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|---|---|---|---|
| page | int | 是 | 頁碼，從 1 開始 |
| limit | int | 是 | 每頁數量 |
| user_id | int | 否 | 登錄後傳入，後端會按當前用戶檔位計算剩餘可抽張數 |

### 每個卡池 `results.data[i]` 額外獎勵相關字段

| 字段 | 類型 | 說明 |
|---|---|---|
| bonus_rules | array | 本池已啟用的額外獎勵規則列表（按 trigger_at 升序） |
| drawn_total | int | 本池全局已抽付費張數（獎勵卡不計入，與後台 `card_sort.drawn_total` 同口徑） |

`bonus_rules` 元素結構：

```json
{
  "trigger_at": 10,
  "spider_card_id": 428,
  "card_id": 0,
  "card_name": "SSR 鳳凰",
  "en_card_name": "SSR Phoenix",
  "card_img": "https://cdn.xxx.com/cards/phoenix.png",
  "card_tag": "典藏",
  "is_from_spider": 1
}
```

| 字段 | 類型 | 說明 |
|---|---|---|
| trigger_at | int | **觸發點 = 卡池全局第 N 張付費卡**（僅這一張觸發一次，不是每 N 張循環） |
| spider_card_id | int | 獎勵卡在卡庫（`spider_cards`）中的 ID。當前後台一律從卡庫選獎勵卡，此值 > 0 |
| card_id | int | 向後兼容舊規則：舊規則可能直接綁定池內 `card_id`，新規則固定為 0 |
| card_name | string | 獎勵卡中文名稱 |
| en_card_name | string | 獎勵卡英文名稱 |
| card_img | string | 獎勵卡圖片 URL |
| card_tag | string | 獎勵卡標籤（如 SSR、典藏等）。若該卡庫記錄無此字段，返回空字串 |
| is_from_spider | int | `1` = 獎勵卡來自卡庫（新規則），`0` = 來自池內 card（舊規則） |

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
            "spider_card_id": 428,
            "card_id": 0,
            "card_name": "SSR 鳳凰",
            "en_card_name": "SSR Phoenix",
            "card_img": "https://cdn.xxx.com/cards/phoenix.png",
            "card_tag": "典藏",
            "is_from_spider": 1
          },
          {
            "trigger_at": 50,
            "spider_card_id": 429,
            "card_id": 0,
            "card_name": "限定 夏日女神",
            "en_card_name": "Limited Summer Goddess",
            "card_img": "https://cdn.xxx.com/cards/goddess.png",
            "card_tag": "限定",
            "is_from_spider": 1
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
| drawn_total | int | 本池全局已抽付費張數 |

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
        "spider_card_id": 428,
        "card_id": 0,
        "card_name": "SSR 鳳凰",
        "en_card_name": "SSR Phoenix",
        "card_img": "https://cdn.xxx.com/cards/phoenix.png",
        "card_tag": "典藏",
        "is_from_spider": 1
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
| count | int | 是 | 本次抽卡張數（1 = 單抽、10 = 十連、或自訂 1 ~ max_draw_count） |
| user_id | int | 是 | 當前用戶 ID |

### 響應

**獎勵卡走正常的 `cards` 數組返回，追加在付費卡之後，帶 `is_bonus=1` 標記**，APP 需據此做差異化展示：

```json
{
  "status": 200,
  "msg": "獲取成功",
  "results": {
    "status": "processing",
    "task_id": 58921,
    "cards": [
      {
        "card_id": 45,
        "card_name": "SR 海浪",
        "card_img": "https://cdn.xxx.com/cards/wave.png",
        "card_tag": "SR"
      },
      {
        "card_id": 46,
        "card_name": "R 陽光",
        "card_img": "https://cdn.xxx.com/cards/sun.png",
        "card_tag": "R"
      },
      {
        "card_id": 10001,
        "card_sort_id": 0,
        "card_name": "SSR 鳳凰",
        "en_card_name": "SSR Phoenix",
        "card_img": "https://cdn.xxx.com/cards/phoenix.png",
        "card_tag": "典藏",
        "is_bonus": 1,
        "bonus_position": 3290,
        "bonus_rule_id": 7,
        "is_from_spider": 1
      }
    ],
    "order_ids": [],
    "total_cost": 1200
  }
}
```

關鍵點：
- `cards` 數組 = 本次付費抽的卡（前 `count` 張）+ 額外獎勵卡（追加在末尾）
- **獎勵卡免費**，`total_cost` 只計算本次付費張數，與獎勵卡無關
- 獎勵卡**正常建單進背包**，BuildCardOrderJob 會為獎勵卡也創建訂單與 `card_order_details` 記錄
- 獎勵卡（`is_bonus=1`）的典型額外字段：

| 字段 | 類型 | 說明 |
|---|---|---|
| is_bonus | int | 固定 `1`，表示獎勵卡 |
| bonus_position | int | 觸發點（即命中的規則 `trigger_at` 值） |
| bonus_rule_id | int | 命中的規則 ID |
| is_from_spider | int | `1` = 來自卡庫（card_sort_id=0），`0` = 來自池內卡 |

- 響應的 `results.status = "processing"`，最終訂單需透過第 4 節 `getDrawTaskStatus` 查詢
- 獎勵卡的 `card_sort_id = 0`（因為卡庫卡不屬於任何池）

---

## 4. 抽卡結果查詢接口

`POST /api/getDrawTaskStatus`（**需登錄**）

查詢 `confirmDrawOrder` 產生的建單任務狀態。由於建單階段已將獎勵卡合併寫入 `card_draw_tasks.cards_json`，返回的 `cards` 與第 3 節一致，**獎勵卡的 `is_bonus / bonus_position / bonus_rule_id / is_from_spider` 字段會完整保留**。

響應示例：

```json
{
  "status": 200,
  "msg": "獲取成功",
  "results": {
    "task_id": 58921,
    "status": 1,
    "cards": [
      { "card_id": 45, "card_name": "SR 海浪", "card_order_details_id": 88902, ... },
      { "card_id": 46, "card_name": "R 陽光", "card_order_details_id": 88903, ... },
      {
        "card_id": 10001,
        "card_name": "SSR 鳳凰",
        "card_order_details_id": 88904,
        "is_bonus": 1,
        "bonus_position": 3290,
        "bonus_rule_id": 7,
        "is_from_spider": 1,
        ...
      }
    ],
    "total_cost": 1200
  }
}
```

`results.status`：`0` = 建單中、`1` = 已建單（可刷新訂單列表）、`2` = 待人工處理

---

## 5. APP 端展示建議

### 5.1 卡池列表（首頁）角標

```
[卡池封面]
卡池名稱
「第 10 張送 SSR 鳳凰 / 第 50 張送限定夏日女神」  ← bonus_rules 陣列拼接
剩餘 720 張 | 已抽 3280 張
[抽卡]
```

拼接規則：
- 每條規則 → `第 {trigger_at} 張送 {card_name}`
- 多條用 `/ ` 或換行分隔
- 無規則（空陣列）→ 不顯示獎勵角標
- 獎勵卡的 `card_tag` 可作為輔助展示（如標籤樣式）
- **注意**：`trigger_at` 是絕對觸發點，不是間隔。展示文案要準確

### 5.2 卡池詳情頁

```
頂部橫幅：
  本池全局已抽 {drawn_total} 張

獎勵規則區塊（僅當 bonus_rules 非空時顯示）：
  ┌──────────────────────────────────────┐
  │ 🎁 額外獎勵                           │
  │                                      │
  │ 第 10 張 → [圖] SSR 鳳凰              │
  │ 第 50 張 → [圖] 限定 夏日女神          │
  └──────────────────────────────────────┘

抽卡按鈕上方：
  可用：剩餘 720 張 / 每單最多 10 張
```

### 5.3 抽卡動畫 / 結果頁

- 獎勵卡（`is_bonus = 1`）應與付費卡做出**視覺差異**：
  - 金色/特別邊框
  - 進場動畫（如閃光、飛入）
  - 卡面顯示「🎁 額外獎勵」角標
- 獎勵卡在數組末尾追加，動畫/展示時 APP 可根據 `is_bonus` 獨立處理
- 獎勵卡不參與「本次花了多少錢」的計算，UI 上無需顯示價格
- 獎勵卡也會出現在 `getDrawTaskStatus` 的 `cards` 中，查詢結果頁同樣要做差異化展示

### 5.4 邊界情況

| 場景 | APP 應對 |
|---|---|
| bonus_rules 空陣列 | 隱藏獎勵相關 UI |
| drawn_total = 0 | 顯示「0」即可（新卡池從零開始） |
| 獎勵卡庫存不足（後端已跳過） | 用戶無感知，僅在後台 log 記錄，APP 正常展示付費抽卡結果 |
| 一次抽 100 張、跨越多個 trigger_at | 後端逐條發，APP 結果頁 `cards` 末尾會有多張獎勵卡，按 `is_bonus` 判斷即可 |
| getDrawTaskStatus.status = 0 | 輪詢；獎勵卡與付費卡一起等待建單完成 |
| spider_card_id > 0, card_id = 0 | 新規則，獎勵卡來自卡庫，正常展示即可 |

---

## 6. 兼容性說明

- **所有獎勵相關字段都是增量返回**：`bonus_rules` 空陣列、`drawn_total=0` 時等價於「未配置獎勵」，舊版 APP 忽略即可
- **獎勵卡的 `is_bonus` 字段**：舊版 APP 不認識該字段，正常把獎勵卡當普通卡顯示，卡片照常進背包，業務無損
- **數據庫新表/新列不存在時**：後端自動回退（`bonus_rules=[]`、`drawn_total=0`），不會導致接口報錯
- **獎勵卡的額外標記字段（`is_bonus / bonus_position / bonus_rule_id / is_from_spider`）**：僅在獎勵卡上存在，普通卡沒有這些字段，APP 判空即可
- **與既有「會員等級」「每用戶抽卡上限」功能獨立**：獎勵卡不消耗用戶的每用戶上限、不參與 `max_draw_count` 計算，兩者互不影響
- **drawn_total 只計付費卡**：獎勵卡發放不會增加 `drawn_total`，所以規則觸發點是穩定的「第 N 張付費卡」

---

## 7. 後台配置說明（供 APP 理解規則來源）

後台 `saveCardSortBonusRulesWeb` 保存規則時：
- **trigger_at**：每個卡池唯一，不可重複。例如同時配置 `trigger_at=10` 和 `trigger_at=20`，就會在第 10 張和第 20 張各發一次
- **spider_card_id**：必填，從卡庫（`spider_cards`）選擇獎勵卡。新規則不再支持直接綁定池內 `card_id`
- **is_active**：是否啟用，後台可臨時關閉某條規則
- **唯一鍵 `(rule_id, trigger_position)`**：確保同一觸發點全局只發一次，無論多少用戶抽、多少並發請求
