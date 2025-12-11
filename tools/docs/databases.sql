-- phpMyAdmin SQL Dump
-- version 4.4.11
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: 2016-07-15 09:23:39
-- 服务器版本： 5.5.20
-- PHP Version: 5.6.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `data_platform`
--

-- --------------------------------------------------------

--
-- 表的结构 `synchronizationLog`
--

CREATE TABLE IF NOT EXISTS `synchronizationLog` (
  `id` int(11) NOT NULL COMMENT '同步的时间',
  `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '统计的次数',
  `info` text NOT NULL COMMENT '同步信息',
  `executeStartTime` int(11) NOT NULL DEFAULT '0' COMMENT '执行开始时间',
  `executeEndTime` int(11) NOT NULL DEFAULT '0' COMMENT '执行开始时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='同步日志表';

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` char(41) COLLATE utf8_unicode_ci NOT NULL COMMENT '密码',
  `loginToken` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '登录token',
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '邮件',
  `phone` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '手机'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `userAction`
--

CREATE TABLE IF NOT EXISTS `userAction` (
  `id` int(11) NOT NULL COMMENT '主键id',
  `organizationId` int(11) NOT NULL DEFAULT '0' COMMENT '机构Id',
  `organizationName` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构名称',
  `organizationCode` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构代号',
  `serialNumber` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备序列号',
  `clientPlatformType` tinyint(4) NOT NULL DEFAULT '0' COMMENT '客户端平台类别',
  `resourceType` tinyint(11) NOT NULL DEFAULT '0' COMMENT '资源类型',
  `type` smallint(6) NOT NULL DEFAULT '0' COMMENT '统计类型',
  `value` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '统计值',
  `day` int(11) NOT NULL DEFAULT '0' COMMENT '所属日期',
  `startTime` int(11) NOT NULL DEFAULT '0' COMMENT '所属开始时间',
  `endTime` int(11) NOT NULL DEFAULT '0' COMMENT '所属结束时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户行为统计表';

-- --------------------------------------------------------

--
-- 表的结构 `userClick`
--

CREATE TABLE IF NOT EXISTS `userClick` (
  `id` int(11) NOT NULL COMMENT '主键id',
  `organizationId` int(11) NOT NULL DEFAULT '0' COMMENT '机构Id',
  `organizationName` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构名称',
  `organizationCode` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构代号',
  `serialNumber` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备序列号',
  `clientPlatformType` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '客户端平台类别',
  `resourceType` tinyint(4) NOT NULL DEFAULT '0' COMMENT '资源类型',
  `actionTime` int(11) NOT NULL DEFAULT '0' COMMENT '行为发生的时间',
  `categoryId` int(11) NOT NULL DEFAULT '0' COMMENT '分类id',
  `categoryName` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='分类点击统计表';

-- --------------------------------------------------------

--
-- 表的结构 `userSearch`
--

CREATE TABLE IF NOT EXISTS `userSearch` (
  `id` int(11) NOT NULL COMMENT '主键id',
  `organizationId` int(11) NOT NULL DEFAULT '0' COMMENT '机构Id',
  `organizationName` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构名称',
  `organizationCode` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构代号',
  `serialNumber` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备序列号',
  `clientPlatformType` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '客户端平台类别',
  `resourceType` tinyint(4) NOT NULL DEFAULT '0' COMMENT '资源类型',
  `actionTime` int(11) NOT NULL DEFAULT '0' COMMENT '行为发生的时间',
  `keyword` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '搜索的关键字'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='搜索统计表';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `synchronizationLog`
--
ALTER TABLE `synchronizationLog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userAction`
--
ALTER TABLE `userAction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userClick`
--
ALTER TABLE `userClick`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userSearch`
--
ALTER TABLE `userSearch`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `userAction`
--
ALTER TABLE `userAction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id';
--
-- AUTO_INCREMENT for table `userClick`
--
ALTER TABLE `userClick`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id';
--
-- AUTO_INCREMENT for table `userSearch`
--
ALTER TABLE `userSearch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id';