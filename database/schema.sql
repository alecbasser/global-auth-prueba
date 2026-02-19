-- Education Resources Manager - Database Schema
-- Table: Resource tracking (views/downloads)

CREATE TABLE IF NOT EXISTS `{prefix}erm_resource_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `action_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `action_type` varchar(20) NOT NULL DEFAULT 'view',
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `user_id` (`user_id`),
  KEY `action_date` (`action_date`),
  KEY `action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: Replace {prefix} with your WordPress table prefix (e.g. wp_)
