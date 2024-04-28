CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint COMMENT 'Unique user identifier',
  `first_name` CHAR(255) NOT NULL DEFAULT '' COMMENT 'User''s first name',
  `last_name` CHAR(255) DEFAULT NULL COMMENT 'User''s last name',
  `inline_name` CHAR(255) NOT NULL DEFAULT '' COMMENT 'User''s inline name',
  `username` CHAR(191) DEFAULT NULL COMMENT 'User''s username',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Entry date creation',
  `request_for_multiplayer` tinyint(1) DEFAULT 0 COMMENT 'Is true if player request multiplayer',

  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `game` (
  `id` bigint(10) UNSIGNED AUTO_INCREMENT COMMENT 'Unique identifier for this game',
  `player_host` bigint COMMENT 'Unique user_host identifier',
  `player_guest` bigint DEFAULT NULL COMMENT 'Unique user_guest identifier',
  `json_key` TEXT COMMENT 'For keep json key',
  `player_last` bigint DEFAULT NULL COMMENT 'Unique player identifier for winner or turn in a game',
  `game_is_over` tinyint(1) DEFAULT 0 COMMENT 'Is true if the game is over',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Entry date creation',

  PRIMARY KEY (`id`),
  KEY `player_host` (`player_host`),
  KEY `player_guest` (`player_guest`),
  KEY `player_last` (`player_last`),
  FOREIGN KEY (`player_host`) REFERENCES `user` (`id`),
  FOREIGN KEY (`player_guest`) REFERENCES `user` (`id`),
  FOREIGN KEY (`player_last`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci
  AUTO_INCREMENT=1000000000;