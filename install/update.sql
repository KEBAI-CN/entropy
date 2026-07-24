ALTER TABLE `wallet_transactions`
ADD COLUMN `biz_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '业务类型' AFTER `remark`,
ADD COLUMN `biz_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '业务单号' AFTER `biz_type`;

ALTER TABLE `wallet_transactions`
ADD UNIQUE KEY `uniq_wallet_transactions_biz` (`biz_type`, `biz_no`);

SET @card_claim_is_claimed_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'card_keys'
      AND COLUMN_NAME = 'is_claimed'
);
SET @card_claim_is_claimed_sql := IF(
    @card_claim_is_claimed_exists = 0,
    'ALTER TABLE `card_keys` ADD COLUMN `is_claimed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''是否已取卡: 0未取卡 1已取卡'' AFTER `sold_time`',
    'SELECT 1'
);
PREPARE card_claim_stmt FROM @card_claim_is_claimed_sql;
EXECUTE card_claim_stmt;
DEALLOCATE PREPARE card_claim_stmt;

SET @card_claim_time_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'card_keys'
      AND COLUMN_NAME = 'claimed_time'
);
SET @card_claim_time_sql := IF(
    @card_claim_time_exists = 0,
    'ALTER TABLE `card_keys` ADD COLUMN `claimed_time` DATETIME DEFAULT NULL COMMENT ''取卡时间'' AFTER `is_claimed`',
    'SELECT 1'
);
PREPARE card_claim_stmt FROM @card_claim_time_sql;
EXECUTE card_claim_stmt;
DEALLOCATE PREPARE card_claim_stmt;

INSERT INTO `system_settings` (`key`, `value`, `remark`) VALUES
('shop_order_weak_password_block_enabled', '1', '是否禁止弱口令下单'),
('shop_order_weak_password_list', '1|11|111|1111|11111|12|123|1234|12345|123456|1234567|12345678|123456789|012345|0123456|01234567|012345678|234567|345678|456789|000000|111111|222222|333333|444444|555555|666|6666|66666|666666|777777|888888|999999|00000000|11111111|123123|112233|121212|654321|987654|876543|765432|543210|5201314|1314520|qwerty|abc123|password|admin|test|guest|asdfgh|1q2w3e|1qaz2wsx', '下单弱口令列表，使用 | 分隔')
ON DUPLICATE KEY UPDATE
`value` = CASE
    WHEN `key` = 'shop_order_weak_password_block_enabled'
        AND (`value` = '' OR `value` = '0')
        THEN VALUES(`value`)
    WHEN `key` = 'shop_order_weak_password_list'
        AND (`value` = '' OR `value` = '123456|123456789|12345678|111111|000000|888888|666666|123123|112233|5201314|1314520|qwerty|abc123|password|admin|test|guest|asdfgh|1q2w3e|1qaz2wsx' OR `value` = '1|11|111|1111|11111|12|123|1234|12345|123456|1234567|12345678|123456789|000000|111111|222222|333333|444444|555555|666|6666|66666|666666|777777|888888|999999|00000000|11111111|123123|112233|121212|654321|5201314|1314520|qwerty|abc123|password|admin|test|guest|asdfgh|1q2w3e|1qaz2wsx')
        THEN VALUES(`value`)
    ELSE `value`
END,
`remark` = VALUES(`remark`);
