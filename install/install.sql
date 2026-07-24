-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-02-14 04:17:13
-- 服务器版本： 5.7.31
-- PHP 版本： 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库：请先在目标环境中选择/创建你自己的数据库后再导入本文件
--

-- --------------------------------------------------------

--
-- 表的结构 `admin_login_log`
--

CREATE TABLE `admin_login_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录用户名',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '登录IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'User Agent',
  `status` int(1) DEFAULT '1' COMMENT '登录状态: 1=成功, 0=失败',
  `msg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '提示信息',
  `create_time` datetime DEFAULT NULL COMMENT '登录时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'info' COMMENT '类型: info, warning, error, success',
  `status` int(1) DEFAULT '0' COMMENT '状态: 0=草稿, 1=已发布',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读: 0=未读, 1=已读',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar` text COLLATE utf8mb4_unicode_ci COMMENT '头像',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邮箱',
  `qq` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `role` int(1) DEFAULT '2' COMMENT '角色: 1=超级管理员, 2=普通管理员',
  `group_id` int(11) DEFAULT '0' COMMENT '权限组ID',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1=启用, 0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '软删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `articles`
--

CREATE TABLE `articles` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'notice' COMMENT '类型: notice, news, tutorial',
  `identifier` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文章访问标识',
  `status` int(1) DEFAULT '1' COMMENT '状态: 0=草稿, 1=发布',
  `is_top` int(1) DEFAULT '0' COMMENT '是否置顶: 0=否, 1=是',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `auth_groups`
--

CREATE TABLE `auth_groups` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `rules` text COLLATE utf8mb4_unicode_ci COMMENT '权限规则ID (JSON或逗号分隔)',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1=启用, 0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '软删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `buyer_blacklists`
--

CREATE TABLE `buyer_blacklists` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'Type: ip, fingerprint, email, user_id',
  `value` varchar(255) NOT NULL COMMENT 'Blocked Value',
  `user_id` int(11) DEFAULT NULL COMMENT 'Associated User ID if known',
  `remark` text COMMENT 'Reason/Remark',
  `admin_id` int(11) DEFAULT NULL COMMENT 'Created by Admin',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Buyer Blacklist';

-- --------------------------------------------------------

--
-- 表的结构 `card_keys`
--

CREATE TABLE `card_keys` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '所属用户ID',
  `product_id` int(11) DEFAULT '0' COMMENT '商品ID',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡密内容',
  `status` int(1) DEFAULT '0' COMMENT '状态: 0=未售, 1=已售, 2=锁定',
  `order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '关联订单号',
  `sold_time` datetime DEFAULT NULL COMMENT '售出时间',
  `is_claimed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已取卡: 0未取卡 1已取卡',
  `claimed_time` datetime DEFAULT NULL COMMENT '取卡时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除',
  KEY `idx_card_keys_product_status` (`product_id`, `status`, `delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '所属用户ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类访问标识',
  `product_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '商品类型: 1=卡密, 2=实物, 3=知识付费',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1=启用, 0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除',
  CONSTRAINT `chk_categories_product_type` CHECK (`product_type` IN (1, 2, 3)),
  KEY `idx_categories_public_list` (`user_id`, `status`, `delete_time`, `sort` DESC, `id` DESC),
  KEY `idx_categories_user_type_status` (`user_id`, `product_type`, `status`, `delete_time`, `sort`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'Order ID',
  `trade_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Trade No (redundant)',
  `shop_id` int(11) DEFAULT '0' COMMENT 'Shop ID',
  `user_id` int(11) DEFAULT '0' COMMENT 'User ID (if logged in)',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Complaint Type',
  `reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Complaint Reason',
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Contact Info',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Complaint Password',
  `status` int(1) DEFAULT '0' COMMENT 'Status: 0=Pending, 1=Processing, 2=Seller Win, 3=Buyer Win',
  `admin_reply` text COLLATE utf8mb4_unicode_ci COMMENT 'Admin Reply',
  `seller_reply` text COLLATE utf8mb4_unicode_ci COMMENT 'Seller Reply',
  `images` text COLLATE utf8mb4_unicode_ci COMMENT 'Evidence Images (JSON)',
  `frozen_amount` decimal(10,2) DEFAULT '0.00' COMMENT '冻结金额',
  `frozen_status` int(1) DEFAULT '0' COMMENT '冻结状态 0未冻结 1已冻结 2已解冻 3已扣除',
  `create_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  `buyer_reply` text COLLATE utf8mb4_unicode_ci COMMENT 'Buyer Reply',
  `is_admin_read` tinyint(4) DEFAULT '0' COMMENT '管理员是否已读',
  `is_seller_read` tinyint(4) DEFAULT '0' COMMENT '卖家是否已读',
  `is_buyer_read` tinyint(4) DEFAULT '0' COMMENT '买家是否已读',
  `is_overdue_notified` tinyint(4) DEFAULT '0' COMMENT '是否已发送超时通知'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `complaint_logs`
--

CREATE TABLE `complaint_logs` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `user_type` varchar(20) NOT NULL COMMENT 'buyer, seller, admin',
  `content` text NOT NULL,
  `create_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `customer_service_sessions`
--

CREATE TABLE `customer_service_sessions` (
  `id` int(11) UNSIGNED NOT NULL,
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '店铺ID',
  `seller_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家用户ID',
  `buyer_docking_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '买家(对接用户)ID，访客为0',
  `guest_fingerprint` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访客浏览器指纹ID',
  `buyer_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '买家名称快照',
  `last_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '最后一条消息摘要',
  `last_message_at` datetime DEFAULT NULL COMMENT '最后消息时间',
  `seller_unread` int(11) NOT NULL DEFAULT '0' COMMENT '商家未读数',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1会话中 0已关闭',
  `is_blacklisted` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否被商家拉黑 0否 1是',
  `blacklisted_at` datetime DEFAULT NULL COMMENT '拉黑时间',
  `blacklist_reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拉黑备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客服会话表';

-- --------------------------------------------------------

--
-- 表的结构 `customer_service_messages`
--

CREATE TABLE `customer_service_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` int(11) NOT NULL DEFAULT '0' COMMENT '会话ID',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '店铺ID',
  `sender_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'buyer' COMMENT '发送者类型 buyer/seller/system',
  `sender_id` int(11) NOT NULL DEFAULT '0' COMMENT '发送者ID',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '消息内容',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客服消息表';

-- --------------------------------------------------------

--
-- 表的结构 `customer_service_ws_events`
--

CREATE TABLE `customer_service_ws_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '店铺ID',
  `session_id` int(11) NOT NULL DEFAULT '0' COMMENT '会话ID',
  `buyer_docking_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '买家ID',
  `guest_fingerprint` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访客浏览器指纹ID',
  `event_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer_service' COMMENT '事件类型',
  `payload` text COLLATE utf8mb4_unicode_ci COMMENT '推送负载JSON',
  `pushed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待推送 1已推送',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客服WebSocket事件队列';

-- --------------------------------------------------------

--
-- 表的结构 `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '所属商户ID',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '优惠券代码',
  `type` tinyint(4) DEFAULT '1' COMMENT '优惠券类型: 1=无门槛(固定金额) 2=满减券 3=折扣券',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `min_amount` decimal(10,2) DEFAULT '0.00' COMMENT '最低消费金额(0表示无门槛)',
  `discount` decimal(10,2) DEFAULT '0.00' COMMENT '实际优惠金额(下单时计算写入)',
  `quantity` int(11) DEFAULT '0' COMMENT '发放数量',
  `used_quantity` int(11) DEFAULT '0' COMMENT '已使用数量',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1=正常, 0=禁用',
  `is_platform` int(1) DEFAULT '0' COMMENT '是否平台券: 1=是, 0=否',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '软删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `deposit_orders`
--

CREATE TABLE `deposit_orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `order_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单号',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '金额',
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unknown' COMMENT '支付方式',
  `status` int(1) DEFAULT '0' COMMENT '状态 0:待支付 1:已支付 2:已关闭',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `asset_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'deposit' COMMENT '资产类型',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  `payment_config_id` int(11) DEFAULT '0' COMMENT '支付配置ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `docking_recharge_orders`
--

CREATE TABLE `docking_recharge_orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单号',
  `docking_user_id` int(11) DEFAULT NULL COMMENT '对接用户ID',
  `shop_id` int(11) DEFAULT NULL COMMENT '所属商户ID',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '充值金额',
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unknown' COMMENT '支付方式',
  `payment_id` int(11) DEFAULT '0' COMMENT '支付配置ID',
  `status` int(1) DEFAULT '0' COMMENT '状态: 0待支付 1已支付',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `docking_sites`
--

CREATE TABLE `docking_sites` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '站点名称',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'system' COMMENT '对接类型',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '配置JSON',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='对接站点配置表';

-- --------------------------------------------------------

--
-- 表的结构 `docking_users`
--

CREATE TABLE `docking_users` (
  `id` int(11) UNSIGNED NOT NULL,
  `shop_id` int(11) DEFAULT NULL COMMENT '所属店铺ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密码',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号',
  `qq` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'QQ号',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'API密钥',
  `register_time` int(11) DEFAULT NULL COMMENT '注册时间',
  `last_login_time` int(11) DEFAULT '0' COMMENT '最后登录时间',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  `status` int(1) DEFAULT '1' COMMENT '状态 1:正常 0:禁用'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `docking_user_balance_logs`
--

CREATE TABLE `docking_user_balance_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `docking_user_id` int(11) DEFAULT NULL COMMENT '对接用户ID',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '变动金额',
  `balance_before` decimal(10,2) DEFAULT NULL COMMENT '变动前余额',
  `balance_after` decimal(10,2) DEFAULT NULL COMMENT '变动后余额',
  `type` int(1) DEFAULT NULL COMMENT '类型 1:充值 2:消费 3:退款',
  `order_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '关联订单号',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `invite_codes`
--

CREATE TABLE `invite_codes` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '邀请码归属用户ID，0为管理员生成',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邀请码',
  `status` int(1) DEFAULT '0' COMMENT '状态 0未使用 1已使用',
  `used_by` int(11) DEFAULT '0' COMMENT '使用用户ID',
  `used_at` datetime DEFAULT NULL COMMENT '使用时间',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请码表';

-- --------------------------------------------------------

--
-- 表的结构 `knowledge_articles`
--

CREATE TABLE `knowledge_articles` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属商户ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '文章标题',
  `summary` varchar(500) DEFAULT '' COMMENT '文章摘要/导读(未购买时展示)',
  `content` longtext COMMENT '文章正文(HTML，购买后展示)',
  `cover_image` varchar(255) DEFAULT '' COMMENT '封面图URL',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(降序)',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态: 0=草稿 1=发布',
  `purchase_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '购买次数(从订单表统计的冗余计数)',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除时间',
  KEY `idx_knowledge_articles_user_status` (`user_id`, `status`, `sort`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识文章表';

-- --------------------------------------------------------

--
-- 表的结构 `migrations`
--

CREATE TABLE `migrations` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `operation_logs`
--

CREATE TABLE `operation_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '操作人ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作人用户名',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'user' COMMENT '角色(admin/user)',
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '请求方法',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '请求路径',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作名称',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '操作内容(JSON)',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'User Agent',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- --------------------------------------------------------

--
-- 表的结构 `order_logistics`
--

CREATE TABLE `order_logistics` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `trade_no` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联订单号',
  `tracking_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '快递单号',
  `logistics_info` JSON NULL COMMENT '物流信息JSON',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:1=已发货 2=无需邮寄 3=运输中 4=已签收 5=异常',
  `last_update_time` datetime DEFAULT NULL COMMENT '最后更新时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单物流表';

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE `orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `trade_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单号',
  `user_id` int(11) DEFAULT '0' COMMENT '买家ID(可选)',
  `shop_id` int(11) DEFAULT '0' COMMENT '店铺ID',
  `product_id` int(11) DEFAULT '0' COMMENT '商品ID',
  `product_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '商品类型快照: 1=卡密, 2=实物, 3=知识付费',
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品名称快照',
  `price` decimal(10,2) DEFAULT NULL COMMENT '单价',
  `quantity` int(11) DEFAULT '1' COMMENT '数量',
  `total_price` decimal(10,2) DEFAULT NULL COMMENT '总价',
  `contact` TEXT NULL COMMENT '联系方式/收货信息JSON',
  `contact_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'any' COMMENT '联系方式类型',
  `pay_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unknown' COMMENT '支付方式',
  `payment_channel_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '支付通道名称',
  `status` int(1) DEFAULT '0' COMMENT '状态: 0待支付 1已支付 2已关闭 3已退款',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fee` decimal(10,2) DEFAULT '0.00' COMMENT '手续费',
  `fee_payer` int(1) DEFAULT '1' COMMENT '手续费承担方: 1商户 2买家',
  `payment_method_id` int(11) DEFAULT '0' COMMENT '支付配置ID',
  `create_ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '下单IP',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT 'User Agent',
  `fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Device Fingerprint',
  `notify_info` text COLLATE utf8mb4_unicode_ci COMMENT '通知信息JSON',
  `sms_fee` decimal(10,2) DEFAULT '0.00' COMMENT '短信费',
  `province` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '省份',
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '城市',
  `is_custom_payment` int(1) DEFAULT '0' COMMENT '是否使用卖家自有支付接口 0:否 1:是',
  `docking_user_id` int(11) DEFAULT '0' COMMENT '对接用户ID',
  `coupon_id` int(11) DEFAULT '0' COMMENT '优惠券ID',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `actual_income` decimal(10,2) DEFAULT '0.00' COMMENT '实际收入',
  `frozen_amount` decimal(10,2) DEFAULT '0.00' COMMENT '冻结金额',
  `frozen_status` tinyint(4) DEFAULT '0' COMMENT '冻结状态 0未冻结 1已冻结 2已解冻 3已扣除',
  `frozen_time` datetime DEFAULT NULL COMMENT '冻结时间',
  `unfrozen_time` datetime DEFAULT NULL COMMENT '解冻时间',
  CONSTRAINT `chk_orders_product_type` CHECK (`product_type` IN (1, 2, 3)),
  KEY `idx_orders_shop_type_status_time` (`shop_id`, `product_type`, `status`, `create_time`, `id`),
  KEY `idx_orders_type_status_time` (`product_type`, `status`, `create_time`, `id`),
  KEY `idx_orders_contact_prefix` (`contact`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `payment_configs`
--

CREATE TABLE `payment_configs` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '接口名称',
  `provider` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付提供商: alipay, wxpay, epay',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付类型: pc, h5, face, native, mapi',
  `device_type` tinyint(1) DEFAULT '0' COMMENT '0:All, 1:PC, 2:Mobile',
  `mode` int(11) DEFAULT '0' COMMENT '支付模式: 0=跳转支付, 1=扫码支付',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '配置参数JSON',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1启用 0禁用',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rate` decimal(10,2) DEFAULT '0.00' COMMENT '接口费率(%)',
  `allow_deposit` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '充值可用: 0=不可用 1=可用',
  `polling_strategy` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'polling' COMMENT '账户选择策略: polling=轮询, random=随机, weight=权重'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `payment_accounts`
--

CREATE TABLE `payment_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `payment_config_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联 payment_configs.id',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '账户名称(便于识别)',
  `account_config` json DEFAULT NULL COMMENT '账户配置参数(app_id/private_key/public_key/pid/key等)',
  `weight` smallint UNSIGNED NOT NULL DEFAULT '1' COMMENT '权重(仅权重模式下生效, 数值越大被选中概率越高)',
  `is_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否启用: 0=禁用 1=启用',
  `fail_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '连续失败次数(发送成功后重置)',
  `last_fail_time` datetime DEFAULT NULL COMMENT '最近一次失败时间',
  `last_use_time` datetime DEFAULT NULL COMMENT '最近一次使用时间',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序(降序, 轮询模式下按此顺序)',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付账户池';

-- --------------------------------------------------------

--
-- 表的结构 `payment_qrcodes`
--

CREATE TABLE `payment_qrcodes` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单号',
  `url` text COLLATE utf8mb4_unicode_ci COMMENT '支付链接/二维码内容',
  `expire_time` int(11) DEFAULT '0' COMMENT '过期时间',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `products`
--

CREATE TABLE `products` (
  `id` int(11) UNSIGNED NOT NULL,
  `uuid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '6位随机码',
  `user_id` int(11) DEFAULT '0' COMMENT '所属用户ID',
  `category_id` int(11) DEFAULT '0' COMMENT '分类ID',
  `product_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '商品类型: 1=卡密, 2=实物, 3=知识付费',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '商品介绍',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `ladder_pricing` text COLLATE utf8mb4_unicode_ci COMMENT '阶梯定价JSON',
  `agent_level_prices` text COLLATE utf8mb4_unicode_ci COMMENT '代理等级价格JSON',
  `is_docking` int(1) DEFAULT '0' COMMENT '是否可对接: 1=是, 0=否',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1=上架, 0=下架',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除',
  `market_price` decimal(10,2) DEFAULT '0.00' COMMENT '参考价/划线价',
  `cost_price` decimal(10,2) DEFAULT '0.00' COMMENT '成本价',
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面图',
  `instruction` text COLLATE utf8mb4_unicode_ci COMMENT '使用说明',
  `stock_warning_threshold` int(11) DEFAULT '0' COMMENT '库存预警阈值',
  `fee_payer` tinyint(4) DEFAULT '0' COMMENT '手续费承担方: 0店铺设置, 1商家, 2买家',
  `stock_display_mode` tinyint(4) DEFAULT '0' COMMENT '库存显示方式: 0范围, 1实际',
  `stock_deduct_mode` tinyint(4) DEFAULT '0' COMMENT '减库存方式: 0支付完成, 1拍下',
  `card_order_mode` tinyint(4) DEFAULT '0' COMMENT '发卡顺序: 0顺序, 1随机, 2自选',
  `delivery_mode` tinyint(4) DEFAULT '0' COMMENT '发货模式: 0自动发货, 1手动发货',
  `allow_coupon` tinyint(4) DEFAULT '1' COMMENT '是否允许使用优惠券: 1是, 0否',
  `min_purchase_quantity` int(11) DEFAULT '1' COMMENT '起购数量',
  `max_purchase_quantity` int(11) DEFAULT '0' COMMENT '最大限购数量(0不限)',
  `purchase_limit_type` tinyint(4) DEFAULT '0' COMMENT '限购类型: 0单IP总限购, 1单IP每日限购',
  `enable_distribution` tinyint(4) DEFAULT '0' COMMENT '代理分销: 0关闭, 1开启',
  `is_agent_distributable` tinyint(4) DEFAULT '0' COMMENT '是否允许代理分销',
  `source_product_id` bigint(20) DEFAULT '0' COMMENT 'Original Product ID for docking',
  `markup_type` tinyint(4) DEFAULT '0' COMMENT '0: Fixed amount, 1: Percentage',
  `markup_value` decimal(10,2) DEFAULT '0.00' COMMENT 'Markup value',
  `docking_type` tinyint(4) DEFAULT '0' COMMENT '0: None, 1: Same System',
  `docking_config` text COLLATE utf8mb4_unicode_ci COMMENT '对接配置信息(JSON)',
  `docking_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Docking site URL',
  `shop_docking_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Remote Shop Slug',
  `docking_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Remote API Key',
  `audit_status` int(11) DEFAULT '1' COMMENT 'Audit status: 0=Pending, 1=Approved, 2=Rejected',
  `refusal_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Refusal reason',
  `is_forced_offline` tinyint(4) DEFAULT '0' COMMENT '是否被强制下架: 0=否, 1=是',
  CONSTRAINT `chk_products_product_type` CHECK (`product_type` IN (1, 2, 3)),
  KEY `idx_products_public_list` (`user_id`, `status`, `audit_status`, `is_forced_offline`, `category_id`, `sort`, `id`),
  KEY `idx_products_user_category_type_status` (`user_id`, `category_id`, `product_type`, `status`, `audit_status`, `delete_time`, `sort`, `id`),
  KEY `idx_products_public_type_list` (`user_id`, `product_type`, `status`, `audit_status`, `is_forced_offline`, `category_id`, `sort`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `product_knowledge_article`
--

CREATE TABLE `product_knowledge_article` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID(知识付费类)',
  `article_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '知识文章ID',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(降序)',
  `create_time` datetime DEFAULT NULL,
  UNIQUE KEY `uk_product_article` (`product_id`, `article_id`),
  KEY `idx_article_products` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品-知识文章关联表';

-- --------------------------------------------------------

--
-- 表的结构 `rate_groups`
--

CREATE TABLE `rate_groups` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '费率组名称',
  `rules` json DEFAULT NULL COMMENT '费率规则 JSON',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `shops`
--

CREATE TABLE `shops` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `avatar` text COLLATE utf8mb4_unicode_ci COMMENT '头像',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '店铺名称',
  `notice` text COLLATE utf8mb4_unicode_ci COMMENT '店铺公告',
  `qq` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'QQ号',
  `wechat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '微信号',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号',
  `contact_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系链接',
  `custom_links` text COLLATE utf8mb4_unicode_ci COMMENT '自定义链接JSON',
  `custom_buttons` text COLLATE utf8mb4_unicode_ci COMMENT '自定义按钮JSON',
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '店铺链接后缀',
  `subdomain_prefix` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '绑定二级域名前缀',
  `subdomain_root_domain` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '绑定主域名',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `promotion_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推广文案',
  `payment_methods` text COLLATE utf8mb4_unicode_ci COMMENT 'Payment methods settings JSON',
  `fee_payer` int(1) DEFAULT '0' COMMENT '手续费承担: 0跟随系统, 1商家, 2买家',
  `sms_payer` int(1) DEFAULT '0' COMMENT '短信费承担: 0跟随系统, 1商家, 2买家',
  `status` tinyint(4) DEFAULT '0' COMMENT '店铺状态 0:正常 1:维护',
  `notify_config` text COLLATE utf8mb4_unicode_ci COMMENT '通知配置JSON',
  `template` text COLLATE utf8mb4_unicode_ci COMMENT '店铺模板',
  `approval_status` tinyint(4) DEFAULT '1' COMMENT '0:Pending, 1:Approved, 2:Rejected',
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rejection reason',
  `deposit_amount` decimal(10,2) DEFAULT '0.00' COMMENT '已缴纳保证金金额',
  `deposit_status` int(1) DEFAULT '0' COMMENT '保证金状态 0:未缴纳 1:已缴纳 2:已退回',
  `is_agent_enabled` tinyint(4) DEFAULT '0' COMMENT '是否开启代理功能',
  `agent_code` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '代理对接码',
  `agent_default_level_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '默认代理等级标识',
  `parent_shop_id` int(11) DEFAULT '0' COMMENT '上级代理店铺ID',
  `short_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '店铺推广短链接',
  `popup_content` text COLLATE utf8mb4_unicode_ci COMMENT '店铺弹窗广告内容',
  `popup_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用弹窗广告',
  `home_plugins` text COLLATE utf8mb4_unicode_ci COMMENT '店铺首页扩展插件JSON',
  `health_score` int(11) DEFAULT '100' COMMENT '店铺健康度'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `shop_agents`
--

CREATE TABLE `shop_agents` (
  `id` int(11) UNSIGNED NOT NULL,
  `shop_id` int(11) DEFAULT '0' COMMENT '分销商店铺ID',
  `parent_shop_id` int(11) DEFAULT '0' COMMENT '供货商店铺ID',
  `status` int(1) DEFAULT '1' COMMENT '状态: 1=正常, 0=已解除',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `agent_level_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '代理等级标识',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `shop_qualifications`
--

CREATE TABLE `shop_qualifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'personal' COMMENT '认证类型: personal/enterprise',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '真实姓名/企业名称',
  `id_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '证件号码',
  `files` text COLLATE utf8mb4_unicode_ci COMMENT '证明材料图片JSON',
  `status` int(11) DEFAULT '0' COMMENT '状态: 0待审核 1已通过 2已驳回',
  `reject_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '驳回原因',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `shop_reports`
--

CREATE TABLE `shop_reports` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `content` text COMMENT '举报内容',
  `ip` varchar(50) DEFAULT '' COMMENT '举报者IP',
  `ip_location` varchar(255) DEFAULT '' COMMENT 'IP归属地',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '0' COMMENT '处理状态: 0=待审查, 1=已核实, 2=内容不符',
  `handle_remark` varchar(255) DEFAULT '' COMMENT '处理备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='店铺举报表';

-- --------------------------------------------------------

--
-- 表的结构 `shop_visitors`
--

CREATE TABLE `shop_visitors` (
  `id` int(11) UNSIGNED NOT NULL,
  `shop_id` int(11) DEFAULT '0' COMMENT '店铺ID',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '访客IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '用户代理',
  `visit_time` int(11) DEFAULT '0' COMMENT '访问时间',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='店铺访客记录表';

-- --------------------------------------------------------

--
-- 表的结构 `short_link_logs`
--

CREATE TABLE `short_link_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_url` text,
  `short_url` varchar(255) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `create_time` int(11) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `supply_stores`
--

CREATE TABLE `supply_stores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `shop_name` varchar(255) NOT NULL COMMENT 'Shop Name',
  `description` text COMMENT 'Description',
  `contact_qq` varchar(20) DEFAULT NULL COMMENT 'Contact QQ',
  `contact_wechat` varchar(50) DEFAULT NULL COMMENT 'Contact WeChat',
  `product_ids` json DEFAULT NULL COMMENT 'JSON array of product IDs',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Disabled, 1: Enabled',
  `audit_status` tinyint(4) DEFAULT '0' COMMENT '0:Pending 1:Approved 2:Rejected',
  `refusal_reason` varchar(255) DEFAULT '' COMMENT 'Refusal reason',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Supply Square Stores';

-- --------------------------------------------------------

--
-- 表的结构 `system_crontab`
--

CREATE TABLE `system_crontab` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '任务标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '任务内容',
  `status` int(1) DEFAULT '0' COMMENT '状态:0=禁用,1=启用',
  `last_run_time` int(11) DEFAULT '0' COMMENT '最后执行时间',
  `next_run_time` int(11) DEFAULT '0' COMMENT '下次执行时间',
  `run_count` int(11) DEFAULT '0' COMMENT '执行次数',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统定时任务表';

-- --------------------------------------------------------

--
-- 表的结构 `system_crontab_log`
--

CREATE TABLE `system_crontab_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `crontab_id` int(11) DEFAULT '0' COMMENT '任务ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '任务标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '执行内容',
  `output` text COLLATE utf8mb4_unicode_ci COMMENT '执行输出',
  `status` int(1) DEFAULT '1' COMMENT '执行状态 1:成功 0:失败',
  `error_msg` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `execution_time` decimal(10,3) DEFAULT '0.000' COMMENT '执行耗时(秒)',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='计划任务执行日志';

-- --------------------------------------------------------

--
-- 表的结构 `email_accounts`
--

CREATE TABLE `email_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '账户名称(便于识别)',
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SMTP 服务器地址',
  `port` smallint UNSIGNED NOT NULL DEFAULT '465' COMMENT 'SMTP 端口',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SMTP 用户名(也是发件人地址)',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SMTP 密码',
  `from_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发件人显示名',
  `encryption` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto' COMMENT '加密方式: auto=自动(按端口), ssl=强制SSL, tls=强制STARTTLS, none=不加密',
  `weight` smallint UNSIGNED NOT NULL DEFAULT '1' COMMENT '权重(仅权重模式下生效, 数值越大被选中概率越高)',
  `is_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否启用: 0=禁用 1=启用',
  `fail_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '连续失败次数(发送成功后重置)',
  `last_fail_time` datetime DEFAULT NULL COMMENT '最近一次失败时间',
  `last_send_time` datetime DEFAULT NULL COMMENT '最近一次发送成功时间',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序(降序, 轮询模式下按此顺序)',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮箱账户池';

-- --------------------------------------------------------

--
-- 表的结构 `system_settings`
--

CREATE TABLE `system_settings` (
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置键名',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密码',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邮箱',
  `qq` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `status` int(1) DEFAULT '1' COMMENT '状态 1:启用 0:禁用',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `deposit` decimal(10,2) DEFAULT '0.00' COMMENT '保证金',
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '手机号',
  `operating_balance` decimal(10,2) DEFAULT '0.00' COMMENT '运营余额',
  `frozen_balance` decimal(10,2) DEFAULT '0.00' COMMENT '冻结余额',
  `rate_group_id` int(11) NOT NULL DEFAULT '0' COMMENT '费率组ID',
  `invite_code_id` int(11) NOT NULL DEFAULT '0' COMMENT '绑定的邀请码ID',
  `inviter_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '邀请人用户ID',
  `invite_bound_time` datetime DEFAULT NULL COMMENT '邀请码绑定时间',
  `real_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '真实姓名',
  `id_card` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '身份证号',
  `real_name_status` int(1) DEFAULT '0' COMMENT '实名状态 0:未认证 1:已认证 2:认证中',
  `real_name_time` int(11) DEFAULT '0' COMMENT '认证时间',
  `complaint_notify` int(1) DEFAULT '0' COMMENT '投诉通知开关 0:关闭 1:开启',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `create_ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '注册IP',
  `login_ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '最后登录IP',
  `complaint_notify_channels` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'email,sms' COMMENT '投诉通知渠道 (email,sms)',
  `complaint_notify_config` text COLLATE utf8mb4_unicode_ci COMMENT '投诉通知配置 (JSON)',
  `risk_control` text COLLATE utf8mb4_unicode_ci COMMENT '风控设置(JSON)',
  `real_name_auth_count` int(11) UNSIGNED DEFAULT '0' COMMENT '实名认证次数',
  `qq_openid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'QQ OpenID',
  `qq_unionid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'QQ UnionID',
  `qq_user_info` text COLLATE utf8mb4_unicode_ci COMMENT 'QQ User Info',
  `wx_openid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WeChat OpenID',
  `wx_unionid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WeChat UnionID',
  `wx_user_info` text COLLATE utf8mb4_unicode_ci COMMENT 'WeChat User Info',
  `alipay_user_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Alipay User ID',
  `alipay_user_info` text COLLATE utf8mb4_unicode_ci COMMENT 'Alipay User Info',
  `weibo_uid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Weibo UID',
  `weibo_user_info` text COLLATE utf8mb4_unicode_ci COMMENT 'Weibo User Info',
  `qq_uin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'QQ UIN from Scan Login',
  `weibo_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Weibo ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user_login_logs`
--

CREATE TABLE `user_login_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '用户ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '用户名',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '登录IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'User Agent',
  `status` int(1) DEFAULT '0' COMMENT '登录状态(1成功0失败)',
  `msg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '提示信息',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '登录地点'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户登录日志表';

-- --------------------------------------------------------

--
-- 表的结构 `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'info' COMMENT '类型: info, warning, error, success',
  `is_read` int(1) DEFAULT '0' COMMENT '是否已读: 0=否, 1=是',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user_notification_reads`
--

CREATE TABLE `user_notification_reads` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `notification_id` int(11) DEFAULT NULL COMMENT '通知ID',
  `create_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user_payment_configs`
--

CREATE TABLE `user_payment_configs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付提供商标识，如 alipay, wxpay',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '支付类型: pc, h5, face, native, mapi, epay',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '配置名称',
  `config` json DEFAULT NULL COMMENT '配置参数',
  `status` int(1) DEFAULT '1' COMMENT '状态 0:禁用 1:启用',
  `sort` int(11) DEFAULT '0' COMMENT '排序权重',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `device_type` int(1) DEFAULT '0' COMMENT '设备类型 0:全平台 1:PC端 2:移动端',
  `force_disabled` int(1) DEFAULT '0' COMMENT '是否强制停用 0:否 1:是',
  `mode` tinyint(4) DEFAULT '0' COMMENT 'Payment mode: 0=jump, 1=scan',
  `polling_strategy` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'polling' COMMENT '账户选择策略: polling=轮询, random=随机, weight=权重'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `shop_payment_accounts`
--

CREATE TABLE `shop_payment_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_payment_config_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联 user_payment_configs.id',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '账户名称(便于识别)',
  `account_config` json DEFAULT NULL COMMENT '账户配置参数(app_id/private_key/public_key/pid/key等)',
  `weight` smallint UNSIGNED NOT NULL DEFAULT '1' COMMENT '权重(仅权重模式下生效, 数值越大被选中概率越高)',
  `is_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否启用: 0=禁用 1=启用',
  `fail_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '连续失败次数(发送成功后重置)',
  `last_fail_time` datetime DEFAULT NULL COMMENT '最近一次失败时间',
  `last_use_time` datetime DEFAULT NULL COMMENT '最近一次使用时间',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序(降序, 轮询模式下按此顺序)',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户自定义支付账户池';

-- --------------------------------------------------------

--
-- 表的结构 `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '用户ID',
  `asset_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'balance' COMMENT '资产类型: balance-余额, deposit-保证金',
  `type` tinyint(4) DEFAULT '1' COMMENT '类型 1:收入 2:支出 3:提现 4:退款',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '变动金额',
  `before_balance` decimal(10,2) DEFAULT '0.00' COMMENT '变动前余额',
  `after_balance` decimal(10,2) DEFAULT '0.00' COMMENT '变动后余额',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `biz_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '业务类型',
  `biz_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '业务单号',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户钱包流水表';

-- --------------------------------------------------------

--
-- 表的结构 `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '用户ID',
  `fund_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'balance' COMMENT '资金类型: balance, deposit, operating_balance',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '提现金额',
  `fee` decimal(10,2) DEFAULT '0.00' COMMENT '手续费',
  `actual_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实到金额',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'alipay' COMMENT '提现方式: alipay, wxpay, bank',
  `account` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '提现账号',
  `real_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '真实姓名',
  `payment_code_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款码图片路径',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态 0:待审核 1:已打款 2:已驳回',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注/驳回原因',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '申请时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `batch_id` int(11) DEFAULT '0' COMMENT '批次ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户提现记录表';

-- --------------------------------------------------------

--
-- 表的结构 `withdrawal_accounts`
--

CREATE TABLE `withdrawal_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'alipay' COMMENT '账户类型: alipay, wxpay, bank',
  `account` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '账号',
  `real_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '真实姓名',
  `payment_code_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '收款码图片',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '审核状态: 0待审核 1已通过 2已拒绝',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '审核原因/说明',
  `pending_action` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '待审核动作: create/update/delete',
  `pending_data` text COLLATE utf8mb4_unicode_ci COMMENT '待审核变更数据(JSON)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户提现账户表';

-- --------------------------------------------------------

--
-- 表的结构 `withdrawal_batch`
--

CREATE TABLE `withdrawal_batch` (
  `id` int(11) UNSIGNED NOT NULL,
  `batch_no` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '批次号',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '总金额',
  `total_count` int(11) DEFAULT '0' COMMENT '总数量',
  `status` int(1) DEFAULT '1' COMMENT '状态:1=已生成,2=已导出',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现批次表';

--
-- 转储表的索引
--

--
-- 表的索引 `admin_login_log`
--
ALTER TABLE `admin_login_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `create_time` (`create_time`);

--
-- 表的索引 `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 表的索引 `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_articles_identifier` (`identifier`),
  ADD KEY `status` (`status`,`type`);

--
-- 表的索引 `auth_groups`
--
ALTER TABLE `auth_groups`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `buyer_blacklists`
--
ALTER TABLE `buyer_blacklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_value` (`type`,`value`);

--
-- 表的索引 `card_keys`
--
ALTER TABLE `card_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `status` (`status`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `uk_categories_slug` (`slug`);

--
-- 表的索引 `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_no` (`trade_no`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- 表的索引 `complaint_logs`
--
ALTER TABLE `complaint_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaint_id` (`complaint_id`);

--
-- 表的索引 `customer_service_sessions`
--
ALTER TABLE `customer_service_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_shop_buyer` (`shop_id`,`buyer_docking_user_id`,`guest_fingerprint`),
  ADD KEY `idx_seller_shop` (`seller_user_id`,`shop_id`),
  ADD KEY `idx_guest_fingerprint` (`guest_fingerprint`),
  ADD KEY `idx_last_message_at` (`last_message_at`);

--
-- 表的索引 `customer_service_messages`
--
ALTER TABLE `customer_service_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_create_time` (`create_time`);

--
-- 表的索引 `customer_service_ws_events`
--
ALTER TABLE `customer_service_ws_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pushed_id` (`pushed`,`id`),
  ADD KEY `idx_shop_session` (`shop_id`,`session_id`),
  ADD KEY `idx_buyer` (`buyer_docking_user_id`),
  ADD KEY `idx_guest_fingerprint` (`guest_fingerprint`);

--
-- 表的索引 `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `deposit_orders`
--
ALTER TABLE `deposit_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `docking_recharge_orders`
--
ALTER TABLE `docking_recharge_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `docking_user_id` (`docking_user_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- 表的索引 `docking_sites`
--
ALTER TABLE `docking_sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `docking_users`
--
ALTER TABLE `docking_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_shop_username` (`shop_id`,`username`),
  ADD UNIQUE KEY `idx_api_key` (`api_key`);

--
-- 表的索引 `docking_user_balance_logs`
--
ALTER TABLE `docking_user_balance_logs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `invite_codes`
--
ALTER TABLE `invite_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 表的索引 `knowledge_articles`
--
ALTER TABLE `knowledge_articles`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`version`);

--
-- 表的索引 `operation_logs`
--
ALTER TABLE `operation_logs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `order_logistics`
--
ALTER TABLE `order_logistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_logistics_order_id` (`order_id`),
  ADD KEY `idx_order_logistics_trade_no` (`trade_no`),
  ADD KEY `idx_order_logistics_status_time` (`status`,`last_update_time`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trade_no` (`trade_no`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `create_time` (`create_time`),
  ADD KEY `create_ip` (`create_ip`);

--
-- 表的索引 `payment_configs`
--
ALTER TABLE `payment_configs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `payment_accounts`
--
ALTER TABLE `payment_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_accounts_config_enabled` (`payment_config_id`,`is_enabled`,`sort`,`id`);

--
-- 表的索引 `payment_qrcodes`
--
ALTER TABLE `payment_qrcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`);

--
-- 表的索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `source_product_id` (`source_product_id`);

--
-- 表的索引 `product_knowledge_article`
--
ALTER TABLE `product_knowledge_article`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `rate_groups`
--
ALTER TABLE `rate_groups`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `uk_subdomain_binding` (`subdomain_prefix`,`subdomain_root_domain`),
  ADD UNIQUE KEY `agent_code` (`agent_code`),
  ADD KEY `parent_shop_id` (`parent_shop_id`);

--
-- 表的索引 `shop_agents`
--
ALTER TABLE `shop_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_id` (`shop_id`,`parent_shop_id`),
  ADD KEY `shop_id_2` (`shop_id`),
  ADD KEY `parent_shop_id` (`parent_shop_id`);

--
-- 表的索引 `shop_qualifications`
--
ALTER TABLE `shop_qualifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- 表的索引 `shop_reports`
--
ALTER TABLE `shop_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- 表的索引 `shop_visitors`
--
ALTER TABLE `shop_visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `visit_time` (`visit_time`),
  ADD KEY `ip` (`ip`);

--
-- 表的索引 `short_link_logs`
--
ALTER TABLE `short_link_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `create_time` (`create_time`);

--
-- 表的索引 `supply_stores`
--
ALTER TABLE `supply_stores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `system_crontab`
--
ALTER TABLE `system_crontab`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `system_crontab_log`
--
ALTER TABLE `system_crontab_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crontab_id` (`crontab_id`),
  ADD KEY `idx_create_time` (`create_time`);

--
-- 表的索引 `email_accounts`
--
ALTER TABLE `email_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_accounts_enabled_sort` (`is_enabled`,`sort`,`id`);

--
-- 表的索引 `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`key`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `rate_group_id` (`rate_group_id`);

--
-- 表的索引 `user_login_logs`
--
ALTER TABLE `user_login_logs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `user_notification_reads`
--
ALTER TABLE `user_notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_2` (`user_id`,`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `notification_id` (`notification_id`);

--
-- 表的索引 `user_payment_configs`
--
ALTER TABLE `user_payment_configs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `shop_payment_accounts`
--
ALTER TABLE `shop_payment_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_payment_accounts_config_enabled` (`user_payment_config_id`,`is_enabled`,`sort`,`id`);

--
-- 表的索引 `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_wallet_transactions_biz` (`biz_type`,`biz_no`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `batch_id` (`batch_id`);

--
-- 表的索引 `withdrawal_accounts`
--
ALTER TABLE `withdrawal_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `withdrawal_batch`
--
ALTER TABLE `withdrawal_batch`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_no` (`batch_no`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin_login_log`
--
ALTER TABLE `admin_login_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `auth_groups`
--
ALTER TABLE `auth_groups`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `buyer_blacklists`
--
ALTER TABLE `buyer_blacklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `card_keys`
--
ALTER TABLE `card_keys`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `complaint_logs`
--
ALTER TABLE `complaint_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `customer_service_sessions`
--
ALTER TABLE `customer_service_sessions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `customer_service_messages`
--
ALTER TABLE `customer_service_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `customer_service_ws_events`
--
ALTER TABLE `customer_service_ws_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `deposit_orders`
--
ALTER TABLE `deposit_orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `docking_recharge_orders`
--
ALTER TABLE `docking_recharge_orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `docking_sites`
--
ALTER TABLE `docking_sites`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `docking_users`
--
ALTER TABLE `docking_users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `docking_user_balance_logs`
--
ALTER TABLE `docking_user_balance_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `invite_codes`
--
ALTER TABLE `invite_codes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `knowledge_articles`
--
ALTER TABLE `knowledge_articles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `operation_logs`
--
ALTER TABLE `operation_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `order_logistics`
--
ALTER TABLE `order_logistics`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `payment_configs`
--
ALTER TABLE `payment_configs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `payment_accounts`
--
ALTER TABLE `payment_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `payment_qrcodes`
--
ALTER TABLE `payment_qrcodes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `product_knowledge_article`
--
ALTER TABLE `product_knowledge_article`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `rate_groups`
--
ALTER TABLE `rate_groups`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `shop_agents`
--
ALTER TABLE `shop_agents`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `shop_qualifications`
--
ALTER TABLE `shop_qualifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `shop_reports`
--
ALTER TABLE `shop_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `shop_visitors`
--
ALTER TABLE `shop_visitors`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `short_link_logs`
--
ALTER TABLE `short_link_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `supply_stores`
--
ALTER TABLE `supply_stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `system_crontab`
--
ALTER TABLE `system_crontab`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `system_crontab_log`
--
ALTER TABLE `system_crontab_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `email_accounts`
--
ALTER TABLE `email_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_login_logs`
--
ALTER TABLE `user_login_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_notification_reads`
--
ALTER TABLE `user_notification_reads`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_payment_configs`
--
ALTER TABLE `user_payment_configs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `shop_payment_accounts`
--
ALTER TABLE `shop_payment_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `withdrawal_accounts`
--
ALTER TABLE `withdrawal_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `withdrawal_batch`
--
ALTER TABLE `withdrawal_batch`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
