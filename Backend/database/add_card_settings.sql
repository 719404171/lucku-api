-- 手動執行：在資料庫管理工具（如 Navicat、PhpMyAdmin）中執行以下 SQL
ALTER TABLE `shop_setting`
  ADD COLUMN IF NOT EXISTS `card_recycle_percent` int(11) DEFAULT 0 COMMENT '卡牌回收百分比' AFTER `release_date`,
  ADD COLUMN IF NOT EXISTS `card_sell_percent` int(11) DEFAULT 100 COMMENT '卡牌售賣百分比' AFTER `card_recycle_percent`,
  ADD COLUMN IF NOT EXISTS `card_import_stock` int(11) DEFAULT 1 COMMENT '卡牌導入預設庫存' AFTER `card_sell_percent`;

-- card 表新增 card_recycle 欄位
ALTER TABLE `card`
  ADD COLUMN IF NOT EXISTS `card_recycle` decimal(10,2) DEFAULT 0.00 COMMENT '卡片回收價格' AFTER `card_cost`;

-- product 表新增 product_pay_type 欄位
ALTER TABLE `product`
  ADD COLUMN IF NOT EXISTS `product_pay_type` tinyint(4) DEFAULT 3 COMMENT '支付方式：1=金幣, 2=鑽石, 3=全部' AFTER `product_type`;

-- product 表新增 trading_card_id 和 card_sort_name 欄位
ALTER TABLE `product`
  ADD COLUMN IF NOT EXISTS `trading_card_id` int(11) DEFAULT NULL COMMENT '關聯 trading_cards 表 ID' AFTER `product_sort_id`,
  ADD COLUMN IF NOT EXISTS `card_sort_name` varchar(255) DEFAULT NULL COMMENT '卡池名稱' AFTER `trading_card_id`;

-- card 表新增 card_brand 欄位
ALTER TABLE `card`
  ADD COLUMN IF NOT EXISTS `card_brand` varchar(256) DEFAULT NULL COMMENT '卡牌品牌' AFTER `card_name`;
