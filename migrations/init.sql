CREATE TABLE `roles` (
  `site_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `role` varchar(30) DEFAULT 'owner',
  `token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`site_id`,`user_id`),
  KEY `apikey` (`token`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `webmention_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webmention_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `http_code` int(11) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `raw_response` text,
  PRIMARY KEY (`id`),
  KEY `webmention_id` (`webmention_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `webmentions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `token` varchar(20) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `complete` tinyint(4) NOT NULL DEFAULT '0',
  `source` varchar(255) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `vouch` varchar(255) DEFAULT NULL,
  `callback` varchar(255) DEFAULT NULL,
  `webmention_endpoint` varchar(255) DEFAULT NULL,
  `webmention_status_url` varchar(255) DEFAULT NULL,
  `pingback_endpoint` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4;
