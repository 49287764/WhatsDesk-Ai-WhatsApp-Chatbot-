-- ============================================================
-- BizBot - AI WhatsApp Business Chatbot - Database Schema & Seed Data
-- CodeIgniter 3 + MySQL/MariaDB
-- Run: mysql -u USER -p DBNAME < database/whatsapp_chatbot.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Admin users (panel login)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_seed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Settings (key/value; overrides config files at runtime)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Menu categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `menu_categories`;
CREATE TABLE `menu_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Menu items
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `available` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Knowledge base (curated FAQ answers the bot can use)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `knowledge`;
CREATE TABLE `knowledge` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL,
  `keywords` VARCHAR(255) NULL,
  `answer` TEXT NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Customers (WhatsApp users)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wa_id` VARCHAR(32) NOT NULL,
  `name` VARCHAR(150) NULL,
  `phone` VARCHAR(32) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wa_id` (`wa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Conversations (one per customer; holds bot state machine)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `wa_id` VARCHAR(32) NOT NULL,
  `state` VARCHAR(50) NOT NULL DEFAULT 'idle',
  `state_data` TEXT NULL,
  `bot_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wa_id` (`wa_id`),
  KEY `idx_customer` (`customer_id`),
  CONSTRAINT `fk_conv_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Messages (inbound from webhook, outbound replies)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `wa_id` VARCHAR(32) NOT NULL,
  `direction` ENUM('in','out') NOT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'text',
  `body` TEXT NULL,
  `wa_message_id` VARCHAR(255) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'received',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wa_message` (`wa_message_id`),
  KEY `idx_conv` (`conversation_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Contact messages (public contact form -> admin inbox)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(32) NULL,
  `email` VARCHAR(150) NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Orders (placed through chat)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NULL,
  `wa_id` VARCHAR(32) NOT NULL,
  `customer_name` VARCHAR(150) NULL,
  `customer_address` TEXT NULL,
  `items_json` TEXT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('placed','confirmed','preparing','ready','delivered','cancelled') NOT NULL DEFAULT 'placed',
  `note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wa_id` (`wa_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user: an unclaimed seed row with a random, unusable password.
-- There are NO default credentials. Open /admin and click "Create your
-- account" — that page replaces this seed with your own username + password.
INSERT INTO `admin_users` (`username`, `password`, `is_seed`) VALUES
('admin', '$2y$10$JHcS39BLN54FH.JIf8PYyOYcO8xDBO3w558jYbmaNQDLfqfDfM3Uu', 1);

-- Settings (empty strings mean "use the config file value")
INSERT INTO `settings` (`key`, `value`) VALUES
('business_name', 'Your Business'),
('business_document', ''),
('business_address', '123 Main Street, Your City'),
('business_phone', '+15551234567'),
('business_hours', 'Mon–Sun: 11:00 – 22:00'),
('delivery_info', 'We offer delivery and pickup. Ask us about delivery times and fees for your area.'),
('greeting', 'Hello! 👋 Welcome to {business_name}. I can help you with our menu, prices, opening hours and orders. Just ask!'),
('fallback_msg', 'Sorry, I am having trouble right now. A staff member will get back to you shortly.'),
('owner_wa_id', ''),
('wa_token', ''),
('wa_phone_number_id', ''),
('wa_app_secret', ''),
('wa_verify_token', ''),
('wa_graph_version', 'v25.0'),
('ai_provider', 'openai'),
('ai_api_key', ''),
('ai_model', 'gpt-4o-mini'),
('ai_base_url', 'https://api.openai.com/v1'),
('ai_temperature', '0.3'),
('ai_max_tokens', '800'),
('collect_customer_details', '1'),
('wa_process_inline', '1'),
('cron_key', 'change-me'),
('wa_order_template', ''),
('wa_notify_status', '0'),
('wa_status_message', 'Your order #{order_id} is now {status}.'),
('currency_symbol', '$');

-- Catalog categories (sample — replace with YOUR categories in the admin panel)
INSERT INTO `menu_categories` (`id`, `name`, `sort_order`) VALUES
(1, 'Sample Meals', 1),
(2, 'Sample Snacks', 2),
(3, 'Sample Drinks', 3);

-- Sample catalog items (edit/replace these in the admin panel to match YOUR business)
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `available`, `sort_order`) VALUES
(1, 'Sample Chicken Biryani', 'A hearty biryani with spiced rice and chicken — our most popular dish.', 8.99, 1, 1),
(1, 'Sample Beef Burger', 'Juicy beef patty with fresh lettuce, tomato and house sauce.', 6.49, 1, 2),
(1, 'Sample Pasta Alfredo', 'Creamy pasta with parmesan and garlic bread on the side.', 9.99, 1, 3),
(2, 'Sample Loaded Fries', 'Crispy fries topped with cheese and herbs.', 3.99, 1, 1),
(2, 'Sample Spring Rolls', 'Four crispy vegetable rolls with sweet chilli dip.', 4.49, 1, 2),
(3, 'Sample Fresh Juice', 'Freshly squeezed orange or mango juice.', 2.99, 1, 1),
(3, 'Sample Soft Drink', 'Chilled can of your favourite soft drink.', 1.49, 1, 2);

-- Knowledge base seeds (sample — replace with YOUR FAQs in the admin panel)
INSERT INTO `knowledge` (`question`, `keywords`, `answer`, `active`) VALUES
('What are your opening hours?', 'hours, open, close, timing, when', 'We are open {business_hours}.', 1),
('Where are you located?', 'location, address, where, find', 'You can find us at {business_address}.', 1),
('Do you deliver?', 'delivery, deliver, shipping, ship', '{delivery_info}', 1),
('How can I pay?', 'pay, payment, card, cash', 'We accept cash on delivery, card in store, and bank transfer. Ask us for the options that apply to you.', 1),
('Can I book a table?', 'book, booking, reserve, reservation', 'Yes! Message us with the date, time and number of guests and we will confirm your table.', 1),
('Do you offer discounts?', 'discount, deal, offer, promo', 'We occasionally run promotions. Message us and we will tell you what is current.', 1),
('How long does delivery take?', 'delivery time, how long, wait, shipping', 'Delivery times depend on your location. Ask us and we will give you an estimate.', 1),
('Can I order for a large group?', 'party, group, catering, large order, event', 'Yes — for parties and large orders, message us at least 24 hours ahead so we can prepare.', 1),
('What allergens are in your food?', 'allergen, allergy, gluten, nuts, dairy', 'Some dishes may contain dairy, eggs, gluten or nuts. Please tell us about allergies before ordering.', 1),
('What is included in the Standard Package?', 'standard, included, features, what', 'The Standard Package includes everything in Basic plus priority support and more features. Ask me for the full catalog.', 1);
