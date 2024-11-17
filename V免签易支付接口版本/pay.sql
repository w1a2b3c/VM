-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2021-12-24 14:56:10
-- 服务器版本： 5.6.50-log
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cs_zzwll_cn`
--

-- --------------------------------------------------------

--
-- 表的结构 `pay_order`
--

CREATE TABLE IF NOT EXISTS `pay_order` (
  `id` bigint(20) NOT NULL,
  `pid` bigint(20) NOT NULL,
  `close_date` bigint(20) NOT NULL,
  `create_date` bigint(20) NOT NULL,
  `is_auto` int(1) NOT NULL,
  `notify_url` varchar(255) DEFAULT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `param` varchar(255) DEFAULT NULL,
  `pay_date` bigint(20) NOT NULL,
  `pay_id` varchar(255) DEFAULT NULL,
  `pay_url` varchar(255) DEFAULT NULL,
  `price` double NOT NULL,
  `really_price` double NOT NULL,
  `return_url` varchar(255) DEFAULT NULL,
  `state` int(1) NOT NULL,
  `type` int(1) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `send_mail` int(1) NOT NULL DEFAULT '0',
  `ip` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_qrcode`
--

CREATE TABLE IF NOT EXISTS `pay_qrcode` (
  `id` bigint(20) NOT NULL,
  `pay_url` varchar(255) DEFAULT NULL,
  `price` double NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `vkey` varchar(255) NOT NULL,
  `vvalue` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `setting`
--

INSERT INTO `setting` (`vkey`, `vvalue`) VALUES
('user', 'admin'),
('pass', '$2y$10$t0BlL4SB7Hi6UfQEJogtZuAqyLFnTVs2tGANmIAnnueZNXcR.KMSW'),
('notifyUrl', ''),
('returnUrl', ''),
('key', ''),
('lastheart', '1632315368'),
('lastpay', '1632966901'),
('jkstate', '0'),
('close', '5'),
('payQf', '1'),
('wxpay', ''),
('zfbpay', ''),
('pid', '123456'),
('email', 'z@zzwll.cn'),
('alipay_id', ''),
('time_interval', '10'),
('pay_num', '5'),
('bd_num', '3'),
('ali_cookie', ''),
('ali_cookie_state', '0'),
('cron_key', ''),
('passageway', '2'),
('ali_cookie_time', '1632966600'),
('qq_cookie_state', '0'),
('qq_cookie_time', NULL),
('qq_cookie', NULL),
('qqpay', NULL),
('qq_running_time', NULL),
('ali_running_time', NULL),
('e_host', NULL),
('e_port', NULL),
('e_from_name', NULL),
('e_user_name', NULL),
('e_password', NULL),
('e_from', NULL),
('home_jump', NULL),
('transfer', '1'),
('confirm', '0'),
('ali_home', NULL),
('qq_home', NULL),
('ali_frequency', '10'),
('pay_voice', '0'),
('qq_frequency', '5');

-- --------------------------------------------------------

--
-- 表的结构 `tmp_price`
--

CREATE TABLE IF NOT EXISTS `tmp_price` (
  `price` varchar(255) NOT NULL,
  `oid` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pay_order`
--
ALTER TABLE `pay_order`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_qrcode`
--
ALTER TABLE `pay_qrcode`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`vkey`);

--
-- Indexes for table `tmp_price`
--
ALTER TABLE `tmp_price`
  ADD PRIMARY KEY (`price`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pay_order`
--
ALTER TABLE `pay_order`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pay_qrcode`
--
ALTER TABLE `pay_qrcode`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
