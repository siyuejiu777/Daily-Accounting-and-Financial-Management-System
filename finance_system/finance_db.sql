-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-06-11 16:42:01
-- 服务器版本： 5.7.26
-- PHP 版本： 7.3.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `finance_db`
--

-- --------------------------------------------------------

--
-- 表的结构 `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(11) NOT NULL COMMENT '预算唯一标识',
  `user_id` int(11) NOT NULL COMMENT '关联的用户ID',
  `budget_month` varchar(7) NOT NULL COMMENT '预算月份，格式如 YYYY-MM',
  `amount` decimal(10,2) NOT NULL COMMENT '月度预算额度'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='月度预算表';

--
-- 转存表中的数据 `budgets`
--

INSERT INTO `budgets` (`budget_id`, `user_id`, `budget_month`, `amount`) VALUES
(1, 1, '2026-06', '10.00');

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL COMMENT '分类唯一标识',
  `category_name` varchar(50) NOT NULL COMMENT '分类名称（如餐饮、工资）',
  `type` enum('income','expense') NOT NULL COMMENT '分类类型：income收入，expense支出'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账目分类表';

--
-- 转存表中的数据 `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `type`) VALUES
(1, '餐饮美食', 'expense'),
(2, '交通出行', 'expense'),
(3, '休闲娱乐', 'expense'),
(4, '购物消费', 'expense'),
(5, '日常琐碎', 'expense'),
(6, '医疗保健', 'expense'),
(7, '薪资收入', 'income'),
(8, '奖金福利', 'income'),
(9, '投资理财', 'income'),
(10, '其他收入', 'income');

-- --------------------------------------------------------

--
-- 表的结构 `records`
--

CREATE TABLE `records` (
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `record_time` datetime NOT NULL COMMENT '记账日期(精确到秒)',
  `type` enum('income','expense') NOT NULL COMMENT '收支类型',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `note` varchar(255) DEFAULT NULL COMMENT '备注'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL COMMENT '用户唯一标识',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '加密后的密码',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `created_at`) VALUES
(1, 'testuser', '$2y$10$xhPWyEOKrELMjJn7Cxlw.e76iEbT/YrfjrYE09TISGT.JwfkZoFw.', '2026-06-01 13:51:05');

--
-- 转储表的索引
--

--
-- 表的索引 `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD UNIQUE KEY `uk_user_month` (`user_id`,`budget_month`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- 表的索引 `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`user_id`,`category_id`,`record_time`),
  ADD KEY `category_id` (`category_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '预算唯一标识', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类唯一标识', AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户唯一标识', AUTO_INCREMENT=2;

--
-- 限制导出的表
--

--
-- 限制表 `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
