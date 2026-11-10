-- =============================================
-- 清空所有用户数据 + 抽卡记录 + 订单及关联数据
-- 执行前请确保已备份数据库！
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ========== 订单相关（先清子表） ==========
TRUNCATE TABLE order_snapshot;      ALTER TABLE order_snapshot AUTO_INCREMENT = 1;
TRUNCATE TABLE order_logistics;     ALTER TABLE order_logistics AUTO_INCREMENT = 1;
TRUNCATE TABLE order_comment;       ALTER TABLE order_comment AUTO_INCREMENT = 1;
TRUNCATE TABLE order_details;       ALTER TABLE order_details AUTO_INCREMENT = 1;
TRUNCATE TABLE `order`;             ALTER TABLE `order` AUTO_INCREMENT = 1;

-- ========== 抽卡记录 ==========
TRUNCATE TABLE draw_session;        ALTER TABLE draw_session AUTO_INCREMENT = 1;
TRUNCATE TABLE user_draw_count;     ALTER TABLE user_draw_count AUTO_INCREMENT = 1;
TRUNCATE TABLE card_draw_tasks;     ALTER TABLE card_draw_tasks AUTO_INCREMENT = 1;

-- ========== 用户相关（先清关联表，再清主表） ==========
TRUNCATE TABLE user_reservation;        ALTER TABLE user_reservation AUTO_INCREMENT = 1;
TRUNCATE TABLE user_exchange;           ALTER TABLE user_exchange AUTO_INCREMENT = 1;
TRUNCATE TABLE user_refund;             ALTER TABLE user_refund AUTO_INCREMENT = 1;
TRUNCATE TABLE user_promo_draw;         ALTER TABLE user_promo_draw AUTO_INCREMENT = 1;
TRUNCATE TABLE user_recharge_bonus;     ALTER TABLE user_recharge_bonus AUTO_INCREMENT = 1;
TRUNCATE TABLE user_wallet_record;      ALTER TABLE user_wallet_record AUTO_INCREMENT = 1;
TRUNCATE TABLE user_integral_record;    ALTER TABLE user_integral_record AUTO_INCREMENT = 1;
TRUNCATE TABLE user_address;            ALTER TABLE user_address AUTO_INCREMENT = 1;
TRUNCATE TABLE user_cart;               ALTER TABLE user_cart AUTO_INCREMENT = 1;
TRUNCATE TABLE user_collect;            ALTER TABLE user_collect AUTO_INCREMENT = 1;
TRUNCATE TABLE user_log;                ALTER TABLE user_log AUTO_INCREMENT = 1;
TRUNCATE TABLE user_message;             ALTER TABLE user_message AUTO_INCREMENT = 1;
TRUNCATE TABLE user_member;             ALTER TABLE user_member AUTO_INCREMENT = 1;

-- 用户主表：保留超级管理员(user_id=1)，下一个用户从 2 开始
DELETE FROM users WHERE user_id > 1;
ALTER TABLE users AUTO_INCREMENT = 2;
-- 如果要连 admin 也一起清掉（⚠️ 清完没人能登录后台！），把上面两行改成：
-- TRUNCATE TABLE users;  ALTER TABLE users AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ========== 验证 ==========
SELECT '====== 清空验证 =====' AS info;
SELECT 'order' AS tbl, COUNT(*) AS cnt, AUTO_INCREMENT AS next_id
FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order'
UNION ALL SELECT 'order_details', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_details') FROM order_details
UNION ALL SELECT 'order_snapshot', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_snapshot') FROM order_snapshot
UNION ALL SELECT 'draw_session', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'draw_session') FROM draw_session
UNION ALL SELECT 'user_draw_count', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_draw_count') FROM user_draw_count
UNION ALL SELECT 'users', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users') FROM users
UNION ALL SELECT 'user_wallet_record', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_wallet_record') FROM user_wallet_record
UNION ALL SELECT 'user_address', COUNT(*), (SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_address') FROM user_address;
