SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `la_service_category` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_name` (`name`) USING BTREE,
  INDEX `idx_status_sort` (`status`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务分类表';

CREATE TABLE IF NOT EXISTS `la_service_tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_name` (`name`) USING BTREE,
  INDEX `idx_status_sort` (`status`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆风格标签表';

CREATE TABLE IF NOT EXISTS `la_service_provider` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '绑定用户ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务人员名称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `tag_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '风格标签ID集合，逗号分隔',
  `intro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '简介',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `is_recommend` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐 0-否 1-是',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_user_id` (`user_id`) USING BTREE,
  INDEX `idx_category_status` (`category_id`, `status`) USING BTREE,
  INDEX `idx_status_recommend_sort` (`status`, `is_recommend`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务人员主档表';

CREATE TABLE IF NOT EXISTS `la_service_open_city` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市名称',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-开放',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_city_code` (`city_code`) USING BTREE,
  INDEX `idx_status_sort` (`status`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆开放城市表';

CREATE TABLE IF NOT EXISTS `la_provider_package` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `provider_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐名称',
  `summary` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐简介',
  `service_duration` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务时长',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_provider_status` (`provider_id`, `status`) USING BTREE,
  INDEX `idx_status_sort` (`status`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务套餐表';

CREATE TABLE IF NOT EXISTS `la_provider_package_area_price` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `package_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `region_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '地区层级 province/city/district',
  `region_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '命中地区编码',
  `region_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '命中地区名称',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `city_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县编码',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_package_region` (`package_id`, `region_level`, `region_code`) USING BTREE,
  INDEX `idx_region_match` (`region_level`, `region_code`, `status`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆套餐地区价格表';

CREATE TABLE IF NOT EXISTS `la_provider_schedule` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `provider_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `service_date` date NOT NULL COMMENT '服务日期',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT '档期状态 available/locked/occupied/unavailable',
  `source_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual' COMMENT '来源类型',
  `source_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '来源业务ID',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_provider_date` (`provider_id`, `service_date`) USING BTREE,
  INDEX `idx_status_date` (`status`, `service_date`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务人员档期表';

CREATE TABLE IF NOT EXISTS `la_wedding_profile` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `wedding_date` date DEFAULT NULL COMMENT '婚礼日期',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市名称',
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县编码',
  `district_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县名称',
  `banquet_hotel` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '宴会场地',
  `table_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '桌数规模',
  `budget_min` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '预算下限',
  `budget_max` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '预算上限',
  `style_preference` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '风格偏好',
  `contact_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系方式',
  `remark` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_user_id` (`user_id`) USING BTREE,
  INDEX `idx_wedding_date` (`wedding_date`) USING BTREE,
  INDEX `idx_city_district` (`city_code`, `district_code`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚礼基础档案表';

CREATE TABLE IF NOT EXISTS `la_service_content_template` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_category_id` (`category_id`) USING BTREE,
  INDEX `idx_status_sort` (`status`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务内容模板表';

CREATE TABLE IF NOT EXISTS `la_service_content_template_page` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `template_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
  `title` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面标题',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面说明',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_template_sort` (`template_id`, `sort`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务内容模板页面表';

CREATE TABLE IF NOT EXISTS `la_service_content_template_field` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `page_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板页面ID',
  `label` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段标题',
  `field_key` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段键',
  `field_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `required` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必填',
  `options_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '选项JSON',
  `default_value` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '默认值',
  `placeholder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '占位说明',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_page_sort` (`page_id`, `sort`) USING BTREE,
  INDEX `idx_field_key` (`field_key`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务内容模板字段表';

CREATE TABLE IF NOT EXISTS `la_service_order` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `provider_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `package_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `provider_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务人员名称快照',
  `package_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐名称快照',
  `service_date` date NOT NULL COMMENT '服务日期',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市名称',
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县编码',
  `district_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县名称',
  `price_match_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '价格命中层级',
  `matched_region_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '命中地区编码',
  `matched_region_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '命中地区名称',
  `order_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `payment_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付方式 1-在线支付 2-线下凭证',
  `order_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '订单状态',
  `pay_way` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '在线支付方式',
  `pay_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态 0-未支付 1-已支付',
  `pay_sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付单号',
  `provider_confirm_expire_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '待确认超时时间',
  `pay_expire_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '待支付超时时间',
  `provider_confirm_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员确认时间',
  `provider_reject_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拒单原因',
  `cancel_source` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '取消来源',
  `cancel_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '取消原因',
  `order_terminal` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下单终端',
  `pay_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方流水号',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_sn` (`sn`) USING BTREE,
  INDEX `idx_user_status_date` (`user_id`, `order_status`, `service_date`) USING BTREE,
  INDEX `idx_provider_status_date` (`provider_id`, `order_status`, `service_date`) USING BTREE,
  INDEX `idx_service_date` (`service_date`) USING BTREE,
  INDEX `idx_pay_status` (`pay_status`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务订单表';

CREATE TABLE IF NOT EXISTS `la_service_order_snapshot` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
  `service_date` date NOT NULL COMMENT '服务日期',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
  `province_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
  `city_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市编码',
  `city_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市名称',
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县编码',
  `district_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县名称',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '最终价格',
  `price_match_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '价格命中层级',
  `matched_region_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '命中地区编码',
  `matched_region_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '命中地区名称',
  `provider_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '服务人员快照JSON',
  `package_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '套餐快照JSON',
  `profile_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '婚礼档案快照JSON',
  `template_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '模板填写快照JSON',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_order_id` (`order_id`) USING BTREE,
  INDEX `idx_service_date` (`service_date`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆服务订单快照表';

CREATE TABLE IF NOT EXISTS `la_service_order_offline_voucher` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `provider_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `voucher_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '凭证图片JSON',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提交说明',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态 0-待审核 1-通过 2-驳回',
  `audit_by` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人',
  `audit_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `audit_remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核说明',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_order_id` (`order_id`) USING BTREE,
  INDEX `idx_audit_status` (`audit_status`) USING BTREE,
  INDEX `idx_provider_id` (`provider_id`) USING BTREE,
  INDEX `idx_delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='婚庆线下支付凭证表';

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'service_business', 'trade', '{"online_pay_enabled":1,"offline_voucher_enabled":1,"provider_confirm_timeout_minutes":30,"pay_timeout_minutes":30}', 1773945600, 1773945600
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'service_business' AND `name` = 'trade'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'service_business', 'review', '{"provider_profile_review_mode":"admin","post_review_mode":"admin","comment_review_mode":"provider_then_admin","order_review_mode":"admin"}', 1773945600, 1773945600
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'service_business' AND `name` = 'review'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'service_business', 'interaction', '{"post_enabled":1,"comment_enabled":1,"order_review_enabled":1}', 1773945600, 1773945600
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'service_business' AND `name` = 'interaction'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'service_business', 'notice', '{"system_notice_enabled":1,"mnp_notice_enabled":1,"work_wechat_notice_enabled":0}', 1773945600, 1773945600
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'service_business' AND `name` = 'notice'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'service_business', 'display', '{"provider_center_enabled":1,"dashboard_enabled":1,"wedding_profile_enabled":1}', 1773945600, 1773945600
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'service_business' AND `name` = 'display'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'service_business', 'dashboard_view_users', '[]', 1773945600, 1773945600
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'service_business' AND `name` = 'dashboard_view_users'
);

REPLACE INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES
(179, 0, 'M', '婚庆业务', 'el-icon-Management', 550, '', 'wedding', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(180, 179, 'C', '业务规则配置', '', 100, 'setting.service_business/getConfig', 'service-business', 'wedding/service-business/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(181, 180, 'A', '保存配置', '', 100, 'setting.service_business/setConfig', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(182, 179, 'C', '服务资源中心', '', 90, '', 'resource-center', 'wedding/resource-center/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(183, 182, 'A', '分类列表', '', 100, 'wedding.service_category/lists', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(184, 182, 'A', '分类新增', '', 90, 'wedding.service_category/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(185, 182, 'A', '分类编辑', '', 80, 'wedding.service_category/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(186, 182, 'A', '分类删除', '', 70, 'wedding.service_category/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(187, 182, 'A', '分类详情', '', 60, 'wedding.service_category/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(188, 182, 'A', '分类状态', '', 50, 'wedding.service_category/updateStatus', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(189, 182, 'A', '标签列表', '', 40, 'wedding.service_tag/lists', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(190, 182, 'A', '标签新增', '', 30, 'wedding.service_tag/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(191, 182, 'A', '标签编辑', '', 20, 'wedding.service_tag/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(192, 182, 'A', '标签删除', '', 10, 'wedding.service_tag/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(193, 182, 'A', '标签详情', '', 9, 'wedding.service_tag/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(194, 182, 'A', '标签状态', '', 8, 'wedding.service_tag/updateStatus', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(195, 179, 'C', '服务人员档案', '', 80, 'wedding.service_provider/lists', 'provider', 'wedding/provider/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(196, 195, 'A', '服务人员新增', '', 100, 'wedding.service_provider/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(197, 195, 'A', '服务人员编辑', '', 90, 'wedding.service_provider/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(198, 195, 'A', '服务人员删除', '', 80, 'wedding.service_provider/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(199, 195, 'A', '服务人员详情', '', 70, 'wedding.service_provider/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(200, 195, 'A', '服务人员状态', '', 60, 'wedding.service_provider/updateStatus', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(201, 195, 'A', '绑定用户选项', '', 50, 'wedding.service_provider/userOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(202, 195, 'A', '分类选项', '', 40, 'wedding.service_provider/categoryOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(203, 195, 'A', '标签选项', '', 30, 'wedding.service_provider/tagOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(204, 179, 'C', '开放城市管理', '', 70, 'wedding.service_open_city/lists', 'open-city', 'wedding/open-city/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(205, 204, 'A', '开放城市新增', '', 100, 'wedding.service_open_city/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(206, 204, 'A', '开放城市编辑', '', 90, 'wedding.service_open_city/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(207, 204, 'A', '开放城市删除', '', 80, 'wedding.service_open_city/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(208, 204, 'A', '开放城市详情', '', 70, 'wedding.service_open_city/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(209, 204, 'A', '开放城市状态', '', 60, 'wedding.service_open_city/updateStatus', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(210, 204, 'A', '开放城市选项', '', 50, 'wedding.service_open_city/cityOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(211, 179, 'C', '套餐管理', '', 65, 'wedding.provider_package/lists', 'provider-package', 'wedding/provider-package/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(212, 211, 'A', '套餐新增', '', 100, 'wedding.provider_package/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(213, 211, 'A', '套餐编辑', '', 90, 'wedding.provider_package/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(214, 211, 'A', '套餐删除', '', 80, 'wedding.provider_package/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(215, 211, 'A', '套餐详情', '', 70, 'wedding.provider_package/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(216, 211, 'A', '套餐状态', '', 60, 'wedding.provider_package/updateStatus', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(217, 211, 'A', '套餐服务人员选项', '', 50, 'wedding.provider_package/providerOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(218, 211, 'A', '套餐开放地区选项', '', 40, 'wedding.provider_package/openRegionOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(219, 179, 'C', '档期管理', '', 60, 'wedding.provider_schedule/lists', 'schedule', 'wedding/schedule/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(220, 219, 'A', '档期新增', '', 100, 'wedding.provider_schedule/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(221, 219, 'A', '档期编辑', '', 90, 'wedding.provider_schedule/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(222, 219, 'A', '档期删除', '', 80, 'wedding.provider_schedule/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(223, 219, 'A', '档期详情', '', 70, 'wedding.provider_schedule/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(224, 219, 'A', '档期服务人员选项', '', 60, 'wedding.provider_schedule/providerOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(225, 179, 'C', '婚礼档案管理', '', 50, 'wedding.wedding_profile/lists', 'wedding-profile', 'wedding/wedding-profile/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(226, 225, 'A', '婚礼档案详情', '', 100, 'wedding.wedding_profile/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(227, 179, 'C', '服务内容模板', '', 40, 'wedding.service_content_template/lists', 'service-content-template', 'wedding/service-content-template/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(228, 227, 'A', '模板新增', '', 100, 'wedding.service_content_template/add', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(229, 227, 'A', '模板编辑', '', 90, 'wedding.service_content_template/edit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(230, 227, 'A', '模板删除', '', 80, 'wedding.service_content_template/delete', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(231, 227, 'A', '模板详情', '', 70, 'wedding.service_content_template/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(232, 227, 'A', '模板状态', '', 60, 'wedding.service_content_template/updateStatus', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(233, 227, 'A', '模板分类选项', '', 50, 'wedding.service_content_template/categoryOptions', '', '', '', '', 0, 1, 0, 1773945600, 1773945600);

REPLACE INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES
(234, 179, 'C', '订单管理', '', 35, 'wedding.service_order/lists', 'service-order', 'wedding/service-order/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(235, 234, 'A', '订单列表', '', 100, 'wedding.service_order/lists', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(236, 234, 'A', '订单详情', '', 90, 'wedding.service_order/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(237, 234, 'A', '凭证审核', '', 80, 'wedding.service_order/offlineVoucherAudit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(238, 179, 'C', '线下凭证审核', '', 34, 'wedding.service_order/lists', 'service-order-voucher', 'wedding/service-order-voucher/index', '', '', 0, 1, 0, 1773945600, 1773945600),
(239, 238, 'A', '凭证列表', '', 100, 'wedding.service_order/lists', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(240, 238, 'A', '凭证详情', '', 90, 'wedding.service_order/detail', '', '', '', '', 0, 1, 0, 1773945600, 1773945600),
(241, 238, 'A', '凭证审核', '', 80, 'wedding.service_order/offlineVoucherAudit', '', '', '', '', 0, 1, 0, 1773945600, 1773945600);

SET FOREIGN_KEY_CHECKS=1;
