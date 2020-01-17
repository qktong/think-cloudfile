CREATE TABLE `prefix_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `key` varchar(64) NOT NULL DEFAULT '' COMMENT 'key/文件名 唯一',
  `hash` varchar(32) NOT NULL COMMENT '七牛云的hash值',
  `extension` varchar(6) NOT NULL DEFAULT '' COMMENT '后缀',
  `size` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mime` varchar(64) NOT NULL DEFAULT '0' COMMENT 'mime',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `bucket` varchar(20) NOT NULL DEFAULT '' COMMENT '文件上传的空间',
  `repeat_count` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '重复上传的次数',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`) USING BTREE,
  KEY `idx_hash` (`hash`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='文件';