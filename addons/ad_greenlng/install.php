<?php
global $_W;
$sql = "
DROP TABLE IF EXISTS `ims_adgreenlng_customer`;
CREATE TABLE `ims_adgreenlng_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0' COMMENT '公众号id',
  `partnerid` int(11) NOT NULL DEFAULT '0' COMMENT '经纪人id',
  `recommendpid` int(11) NOT NULL DEFAULT '0' COMMENT '客户推荐经纪人id',
  `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机',
  `houseid` int(11) NOT NULL DEFAULT '0' COMMENT '区域报价id',
  `laststatusid` int(11) NOT NULL DEFAULT '0' COMMENT '客户状态id',
  `remark` varchar(1000) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态,-1关闭,0正常',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mobile` (`mobile`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='客户表';

-- ----------------------------
-- Table structure for ims_adgreenlng_customer_status
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_customer_status`;
CREATE TABLE `ims_adgreenlng_customer_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0' COMMENT '公众号id',
  `displayorder` int(11) NOT NULL DEFAULT '0',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COMMENT='客户状态表';

-- ----------------------------
-- Table structure for ims_adgreenlng_customer_trace
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_customer_trace`;
CREATE TABLE `ims_adgreenlng_customer_trace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0' COMMENT '公众号id',
  `customerid` int(11) NOT NULL DEFAULT '0' COMMENT '客户id',
  `statusid` int(11) NOT NULL DEFAULT '0' COMMENT '客户状态id',
  `partnerid` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `remark` varchar(1000) NOT NULL DEFAULT '' COMMENT '备注',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '奖励佣金',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cspid` (`customerid`,`statusid`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COMMENT='客户状态跟踪表';

-- ----------------------------
-- Table structure for ims_adgreenlng_house
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_house`;
CREATE TABLE `ims_adgreenlng_house` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL DEFAULT '',
  `cid` int(11) NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `phone` varchar(32) NOT NULL DEFAULT '',
  `selleraddress` varchar(512) NOT NULL DEFAULT '',
  `address` varchar(512) NOT NULL DEFAULT '',
  `province` varchar(50) NOT NULL DEFAULT '',
  `city` varchar(50) NOT NULL DEFAULT '',
  `district` varchar(50) NOT NULL DEFAULT '',
  `opentime` int(10) unsigned NOT NULL DEFAULT '0',
  `preferential` varchar(255) NOT NULL DEFAULT '',
  `hotmsg` varchar(255) NOT NULL DEFAULT '',
  `credit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `credit_type` varchar(10) NOT NULL DEFAULT '' COMMENT '积分类型',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00',
  `new_commission` varchar(255) NOT NULL DEFAULT '' COMMENT '佣金展示',
  `longitude` varchar(255) NOT NULL DEFAULT '',
  `latitude` varchar(255) NOT NULL DEFAULT '',
  `geohash` varchar(45) NOT NULL DEFAULT '',
  `coverimg` varchar(255) NOT NULL DEFAULT '',
  `descimgs` mediumtext,
  `description` mediumtext,
  `dynamicdesc` mediumtext,
  `nearby` mediumtext,
  `pricetype` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `specialtype` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '特色',
  `housetype` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `layouttype` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `viewcount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览数',
  `sharecount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享数',
  `commentcount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `displayorder` int(11) NOT NULL DEFAULT '0',
  `recommend` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_house_bespeak
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_house_bespeak`;
CREATE TABLE `ims_adgreenlng_house_bespeak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `houseid` int(11) NOT NULL DEFAULT '0',
  `username` varchar(64) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `bespeaktime` int(10) unsigned NOT NULL DEFAULT '0',
  `remark` mediumtext,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`),
  KEY `indx_houseid` (`houseid`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_house_kv
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_house_kv`;
CREATE TABLE `ims_adgreenlng_house_kv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `houseid` int(11) NOT NULL DEFAULT '0',
  `key` varchar(512) NOT NULL DEFAULT '',
  `value` varchar(512) NOT NULL DEFAULT '',
  `displayorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_houseid` (`houseid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_house_order
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_house_order`;
CREATE TABLE `ims_adgreenlng_house_order` (
  `ordid` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `orderno` varchar(50) NOT NULL DEFAULT '',
  `houseid` int(11) NOT NULL DEFAULT '0',
  `paytype` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1:余额支付,2:在线支付,3:线下支付',
  `transid` varchar(30) NOT NULL DEFAULT '' COMMENT '微信支付',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `realname` varchar(50) NOT NULL DEFAULT '',
  `mobile` varchar(20) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `paydetail` varchar(500) NOT NULL DEFAULT '',
  `paytime` int(10) unsigned NOT NULL DEFAULT '0',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ordid`),
  UNIQUE KEY `uniq_indx_orderno` (`orderno`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_house_share
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_house_share`;
CREATE TABLE `ims_adgreenlng_house_share` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公众号id',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员id',
  `house_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '区域报价id',
  `friend_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '好友会员id 0为游客',
  `credit_type` varchar(10) NOT NULL DEFAULT '' COMMENT '积分类型',
  `credit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '消费积分',
  `ip` char(15) NOT NULL DEFAULT '' COMMENT 'ip',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间戳',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`,`uid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for ims_adgreenlng_house_type
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_house_type`;
CREATE TABLE `ims_adgreenlng_house_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公众号id',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1:特色 2:类型',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `isshow` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `displayorder` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`,`type`)
) ENGINE=MyISAM AUTO_INCREMENT=63 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_layout
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_layout`;
CREATE TABLE `ims_adgreenlng_layout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `houseid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(256) NOT NULL DEFAULT '',
  `img` varchar(512) NOT NULL DEFAULT '',
  `area` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tag` varchar(256) NOT NULL DEFAULT '',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_looking
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_looking`;
CREATE TABLE `ims_adgreenlng_looking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL DEFAULT '',
  `slide` text,
  `viewtime` int(10) unsigned NOT NULL DEFAULT '0',
  `regdeadline` int(10) unsigned NOT NULL DEFAULT '0',
  `longitude` varchar(255) NOT NULL DEFAULT '',
  `latitude` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `contact` varchar(32) NOT NULL DEFAULT '',
  `gatheraddress` varchar(500) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `remark` mediumtext,
  `displayorder` int(11) NOT NULL DEFAULT '0',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_looking_house
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_looking_house`;
CREATE TABLE `ims_adgreenlng_looking_house` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `lookid` int(11) NOT NULL DEFAULT '0',
  `houseid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indx_lookid` (`lookid`,`houseid`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_looking_users
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_looking_users`;
CREATE TABLE `ims_adgreenlng_looking_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `lookid` int(11) NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `message` mediumtext,
  `fellows` tinyint(4) NOT NULL DEFAULT '0',
  `likehouse` mediumtext,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`),
  KEY `indx_lookid` (`lookid`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_navigation
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_navigation`;
CREATE TABLE `ims_adgreenlng_navigation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公众号id',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '链接',
  `displayorder` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `isshow` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否显示 1显示 0不显示',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for ims_adgreenlng_new_commission
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_new_commission`;
CREATE TABLE `ims_adgreenlng_new_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remark` varchar(500) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tid` int(11) NOT NULL DEFAULT '0' COMMENT 'tidtype对应id值',
  `tidtype` varchar(20) NOT NULL DEFAULT '' COMMENT 'ordid/customerid',
  `payment_no` varchar(100) NOT NULL DEFAULT '',
  `order_no` varchar(50) NOT NULL DEFAULT '',
  `reason` varchar(500) NOT NULL DEFAULT '',
  `message` varchar(500) NOT NULL DEFAULT '',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`),
  KEY `indx_uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_partner
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_partner`;
CREATE TABLE `ims_adgreenlng_partner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '3.0升级已弃用',
  `subuid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '经纪人uid',
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  `roleid` int(11) NOT NULL DEFAULT '0',
  `realname` varchar(50) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `customer_total` int(11) NOT NULL DEFAULT '0' COMMENT '客户数',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '-1未审核1启用2禁用',
  `invite_qrcode` varchar(100) NOT NULL DEFAULT '',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indx_uid` (`uid`,`subuid`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_partner_house_ref
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_partner_house_ref`;
CREATE TABLE `ims_adgreenlng_partner_house_ref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partnerid` int(11) NOT NULL DEFAULT '0' COMMENT '经纪人id',
  `houseid` int(11) NOT NULL DEFAULT '0' COMMENT '区域报价id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_phid` (`partnerid`,`houseid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='区域报价项目经理关系表';

-- ----------------------------
-- Table structure for ims_adgreenlng_partner_rel
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_partner_rel`;
CREATE TABLE `ims_adgreenlng_partner_rel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `partnerid` int(10) unsigned NOT NULL DEFAULT '0',
  `subpartnerid` int(10) unsigned NOT NULL DEFAULT '0',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indx_partnerid` (`partnerid`,`subpartnerid`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ims_adgreenlng_partner_role
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_partner_role`;
CREATE TABLE `ims_adgreenlng_partner_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0' COMMENT '公众号id',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `displayorder` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `isshow` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `isadmin` tinyint(4) NOT NULL DEFAULT '0' COMMENT '管理权限(0:否 1:是)',
  `issubadmin` tinyint(4) NOT NULL DEFAULT '0' COMMENT '子管理权限(0:否 1:是)',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='经纪人身份类型表';

-- ----------------------------
-- Table structure for ims_adgreenlng_stat
-- ----------------------------
DROP TABLE IF EXISTS `ims_adgreenlng_stat`;
CREATE TABLE `ims_adgreenlng_stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公众号id',
  `daytime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '日期',
  `house_views` int(11) NOT NULL DEFAULT '0' COMMENT '区域报价浏览数',
  `house_shares` int(11) NOT NULL DEFAULT '0' COMMENT '区域报价分享数',
  `house_comments` int(11) NOT NULL DEFAULT '0' COMMENT '区域报价评论数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `indx_uniacid` (`uniacid`,`daytime`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
";
pdo_query($sql);
?>