ALTER TABLE `skins` MODIFY COLUMN `item_id` bigint(0) UNSIGNED NULL DEFAULT NULL COMMENT 'ZBT itemId' AFTER `hash_name`;
ALTER TABLE `skins` ADD COLUMN `template_id` int NULL DEFAULT NULL COMMENT '有品模板Id' AFTER `item_id`;
ALTER TABLE `skins` ADD UNIQUE INDEX(`template_id`);
#需要把饰品倍数设为1:1
