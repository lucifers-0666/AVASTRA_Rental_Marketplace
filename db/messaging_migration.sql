-- =============================================================================
-- AVASTRA — Messaging Migration
-- Adds the two tables the schema was missing for user/messages.php to work.
-- Run this once in phpMyAdmin (SQL tab, on the spaceshare_db database) or
-- have Zaid add it to db/schema.sql so it's part of the shared setup.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `conversations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `space_id` INT DEFAULT NULL,
  `user_one_id` INT NOT NULL,
  `user_two_id` INT NOT NULL,
  `last_message_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_one_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_two_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `body` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: a couple of demo rows so the Messages page isn't empty on first test.
-- Safe to delete later — uses the seed users/spaces that already exist (ids 1-3).
INSERT INTO `conversations` (`space_id`, `user_one_id`, `user_two_id`, `last_message_at`) VALUES
(1, 3, 2, NOW());

INSERT INTO `messages` (`conversation_id`, `sender_id`, `body`, `is_read`, `created_at`) VALUES
(1, 2, 'Hi, your booking is confirmed. Let me know if you have any setup requirements.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 3, 'Thank you! We will need the north-facing section set up. Is that possible?', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 2, 'Absolutely, I will have it set up in time. See you on the day!', 0, DATE_SUB(NOW(), INTERVAL 1 DAY));
