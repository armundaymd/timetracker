CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `api_token` varchar(64) DEFAULT NULL, -- For mobile app / calendar subscription
  PRIMARY KEY (`id`)
);

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#0ea5e9',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
);

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `is_running` tinyint(1) DEFAULT 0,
  `project_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `project_id` (`project_id`)
);

-- Starred task titles for quick reuse (from "What are you doing?" input)
CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_title` (`user_id`, `title`),
  KEY `user_id` (`user_id`)
);

-- Quick-action buttons shown next to the task input (configured in Settings)
CREATE TABLE `user_quick_buttons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
);

-- Insert a default user (Username: admin, Password: password)
-- You should change the password immediately after logging in or manually via MD5/Hash
INSERT INTO `users` (`username`, `password`, `api_token`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bk32-secret-token-8842');

-- If you already have the app installed, run only these to add projects + heatmap support:
-- CREATE TABLE IF NOT EXISTS `projects` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `name` varchar(50) NOT NULL, `color` varchar(7) DEFAULT '#0ea5e9', PRIMARY KEY (`id`), KEY `user_id` (`user_id`) );
-- ALTER TABLE `tasks` ADD COLUMN `project_id` int(11) DEFAULT NULL, ADD KEY `project_id` (`project_id`);