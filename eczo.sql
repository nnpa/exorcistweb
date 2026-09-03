-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Сен 03 2026 г., 14:49
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `eczo`
--

-- --------------------------------------------------------

--
-- Структура таблицы `auction`
--

CREATE TABLE `auction` (
  `id` int NOT NULL,
  `seller_id` int NOT NULL,
  `item_id` varchar(36) NOT NULL,
  `price` int NOT NULL,
  `start_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `end_time` datetime DEFAULT ((now() + interval 7 day)),
  `sold` tinyint(1) DEFAULT '0',
  `buyer_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `auction_lots`
--

CREATE TABLE `auction_lots` (
  `id` int NOT NULL,
  `seller_id` int NOT NULL,
  `price` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` tinyint DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `auction_lot_items`
--

CREATE TABLE `auction_lot_items` (
  `lot_id` int NOT NULL,
  `item_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `characters`
--

CREATE TABLE `characters` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(64) NOT NULL,
  `level` int DEFAULT '1',
  `experience` int DEFAULT '0',
  `health` int DEFAULT '100',
  `max_health` int DEFAULT '100',
  `mana` int DEFAULT '50',
  `max_mana` int DEFAULT '50',
  `gold` int DEFAULT '100',
  `current_dungeon` varchar(64) DEFAULT 'dungeon_1',
  `difficulty` int DEFAULT '1',
  `last_dungeon_position_x` float DEFAULT '0',
  `last_dungeon_position_y` float DEFAULT '2.5',
  `last_dungeon_position_z` float DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `health_potions` int NOT NULL DEFAULT '0',
  `mana_potions` int NOT NULL DEFAULT '0',
  `damage` int NOT NULL DEFAULT '0',
  `defense` int NOT NULL DEFAULT '0',
  `kill_counter` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `characters`
--

INSERT INTO `characters` (`id`, `user_id`, `name`, `level`, `experience`, `health`, `max_health`, `mana`, `max_mana`, `gold`, `current_dungeon`, `difficulty`, `last_dungeon_position_x`, `last_dungeon_position_y`, `last_dungeon_position_z`, `created_at`, `updated_at`, `health_potions`, `mana_potions`, `damage`, `defense`, `kill_counter`) VALUES
(2, 2, 'Padaboo', 50, 0, 100, 630, 50, 310, 92195, 'dungeon_1', 1, -361.819, 3.38645, 305.729, '2026-08-15 19:39:31', '2026-09-03 11:57:32', 17, 9, 115, 26, 0),
(3, 3, 'test', 1, 0, 100, 100, 50, 50, 100, 'dungeon_1', 1, 0, 2.5, 0, '2026-09-02 23:16:10', '2026-09-02 23:16:10', 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `character_talents`
--

CREATE TABLE `character_talents` (
  `id` int NOT NULL,
  `character_id` int NOT NULL,
  `talent_id` varchar(50) NOT NULL,
  `level` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `character_talents`
--

INSERT INTO `character_talents` (`id`, `character_id`, `talent_id`, `level`) VALUES
(441, 2, 'attack_1', 5),
(442, 2, 'attack_2', 3),
(443, 2, 'attack_3', 3),
(444, 2, 'attack_4', 2),
(445, 2, 'attack_5', 4),
(446, 2, 'attack_6', 3),
(447, 2, 'attack_7', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `character_id` int NOT NULL,
  `item_id` varchar(36) NOT NULL,
  `slot_index` int DEFAULT NULL,
  `equipped` tinyint(1) DEFAULT '0',
  `equipped_slot` enum('helmet','chest','weapon','shield','legs','boots','gloves') DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `inventory`
--

INSERT INTO `inventory` (`id`, `character_id`, `item_id`, `slot_index`, `equipped`, `equipped_slot`, `created_at`) VALUES
(225, 2, 'e015cf5d-1bfc-44bc-bf01-ddbd4fa37c33', NULL, 1, 'boots', '2026-09-02 23:47:45'),
(226, 2, '2ef1b75a-0212-4dbd-87c4-9ef32b7df5ad', NULL, 1, 'gloves', '2026-09-02 23:47:49'),
(227, 2, '0a3c1305-701b-4eb7-ba0f-35d35a6a68f9', 10, 0, NULL, '2026-09-02 23:48:19'),
(228, 2, 'e7101bec-4150-4cd7-81f2-fe631b691d4a', NULL, 1, 'weapon', '2026-09-02 23:49:26'),
(229, 2, '9783c57d-daad-45e5-a7ec-3f9662b49d87', 0, 0, NULL, '2026-09-02 23:49:37'),
(230, 2, '9e423447-9fc2-4729-b77c-ddae83d4a6d9', NULL, 1, 'legs', '2026-09-02 23:49:38'),
(231, 2, 'dfd60908-71c9-4653-908d-3db90131de62', 2, 0, NULL, '2026-09-02 23:50:16'),
(232, 2, '1246a0b1-7e18-4269-9717-f6b2391cfc31', 4, 0, NULL, '2026-09-02 23:50:17'),
(233, 2, '1c57d59a-c0aa-47b3-ad8f-fbcd2720c391', NULL, 1, 'shield', '2026-09-02 23:53:06'),
(234, 2, 'f0f4f250-b3a3-49ba-b179-1e2cfdb00b41', NULL, 1, 'helmet', '2026-09-02 23:53:07'),
(235, 2, 'bfb5b9cf-8f87-4103-8b2a-3bec040d8991', NULL, 1, 'chest', '2026-09-02 23:53:30'),
(236, 2, '47ed11fd-5288-4060-811d-e893252855dd', 6, 0, NULL, '2026-09-02 23:54:00'),
(237, 2, '74a2b39f-abb0-4e46-8421-87a4e75a2355', 3, 0, NULL, '2026-09-02 23:58:26'),
(238, 2, '7c2d5cb5-7f87-4ae2-82c3-960a673a472e', 5, 0, NULL, '2026-09-03 11:21:50'),
(239, 2, '4b5de310-fb9d-4edc-a1b3-cf0bfe5d5824', 7, 0, NULL, '2026-09-03 11:24:55'),
(240, 2, '0e18593e-0743-4deb-b325-dbdd22651033', 1, 0, NULL, '2026-09-03 11:57:15'),
(241, 2, 'e51cd5a7-649a-4812-9231-da0d1d5e2b82', 9, 0, NULL, '2026-09-03 11:57:15'),
(243, 2, 'ab0d541e-99c3-466a-92dd-38d652a1ef0f', 11, 0, NULL, '2026-09-03 11:57:16'),
(244, 2, 'a6bf9a4b-c099-4c5c-949b-2d93b8d0db82', 12, 0, NULL, '2026-09-03 11:57:16');

-- --------------------------------------------------------

--
-- Структура таблицы `items`
--

CREATE TABLE `items` (
  `id` varchar(36) NOT NULL,
  `name` varchar(128) NOT NULL,
  `type` enum('Weapon','Helmet','Chest','Shield','Legs','Boots','Gloves','Gem') NOT NULL,
  `level` int NOT NULL,
  `rarity` enum('COMMON','UNCOMMON','RARE','EPIC','LEGENDARY') NOT NULL,
  `description` text,
  `damage` int DEFAULT '0',
  `defense` int DEFAULT '0',
  `health_bonus` int DEFAULT '0',
  `mana_bonus` int DEFAULT '0',
  `icon_path` varchar(255) DEFAULT NULL,
  `socket_count` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `difficulty` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `items`
--

INSERT INTO `items` (`id`, `name`, `type`, `level`, `rarity`, `description`, `damage`, `defense`, `health_bonus`, `mana_bonus`, `icon_path`, `socket_count`, `created_at`, `difficulty`) VALUES
('0a3c1305-701b-4eb7-ba0f-35d35a6a68f9', 'Heavy Longsword', 'Weapon', 1, 'COMMON', '', 8, 0, 0, 0, 'Icons/Items/Weapon/9.png', 0, '2026-09-02 23:48:19', 1),
('0e18593e-0743-4deb-b325-dbdd22651033', 'Ancient Longsword', 'Weapon', 50, 'UNCOMMON', '', 132, 0, 0, 0, 'Icons/Items/Weapon/1.png', 2, '2026-09-03 11:57:15', 1),
('1246a0b1-7e18-4269-9717-f6b2391cfc31', 'Sturdy Breastplate', 'Chest', 2, 'COMMON', '', 0, 4, 14, 5, 'Icons/Items/Chest/7.png', 0, '2026-09-02 23:50:17', 1),
('1c57d59a-c0aa-47b3-ad8f-fbcd2720c391', 'Magic Guard', 'Shield', 1, 'RARE', '', 0, 4, 6, 0, 'Icons/Items/Shield/6.png', 1, '2026-09-02 23:53:06', 1),
('2ef1b75a-0212-4dbd-87c4-9ef32b7df5ad', 'Magic Gauntlets', 'Gloves', 1, 'EPIC', '', 0, 1, 0, 3, 'Icons/Items/Gloves/9.png', 2, '2026-09-02 23:47:49', 1),
('47ed11fd-5288-4060-811d-e893252855dd', 'Dark Scimitar', 'Weapon', 3, 'RARE', '', 16, 0, 0, 0, 'Icons/Items/Weapon/6.png', 0, '2026-09-02 23:54:00', 1),
('4b5de310-fb9d-4edc-a1b3-cf0bfe5d5824', 'Rusty Sabre', 'Weapon', 3, 'EPIC', '', 16, 0, 0, 0, 'Icons/Items/Weapon/1.png', 1, '2026-09-03 11:24:55', 1),
('74a2b39f-abb0-4e46-8421-87a4e75a2355', 'Sharp Katana', 'Weapon', 3, 'UNCOMMON', '', 14, 0, 0, 0, 'Icons/Items/Weapon/7.png', 0, '2026-09-02 23:58:26', 1),
('7c2d5cb5-7f87-4ae2-82c3-960a673a472e', 'Sturdy Headguard', 'Helmet', 3, 'EPIC', '', 0, 4, 10, 0, 'Icons/Items/Helmet/2.png', 1, '2026-09-03 11:21:50', 1),
('9783c57d-daad-45e5-a7ec-3f9662b49d87', 'Magic Gloves', 'Gloves', 2, 'UNCOMMON', '', 0, 1, 0, 4, 'Icons/Items/Gloves/3.png', 2, '2026-09-02 23:49:37', 1),
('9e423447-9fc2-4729-b77c-ddae83d4a6d9', 'Rusty Leggings', 'Legs', 2, 'RARE', '', 0, 3, 7, 0, 'Icons/Items/Legs/7.png', 2, '2026-09-02 23:49:38', 1),
('a6bf9a4b-c099-4c5c-949b-2d93b8d0db82', 'Ruby', 'Gem', 1, 'RARE', '', 0, 0, 0, 0, 'Icons/Items/Gem/ruby.png', 0, '2026-09-03 11:57:16', 1),
('ab0d541e-99c3-466a-92dd-38d652a1ef0f', 'Ruby', 'Gem', 1, 'RARE', '', 0, 0, 0, 0, 'Icons/Items/Gem/ruby.png', 0, '2026-09-03 11:57:16', 1),
('b9c7dee6-95a5-488b-9318-4df4ea31ff0e', 'Emerald', 'Gem', 1, 'RARE', '', 0, 0, 0, 0, 'Icons/Items/Gem/emerald.png', 0, '2026-09-03 11:57:16', 1),
('bfb5b9cf-8f87-4103-8b2a-3bec040d8991', 'Old Chestplate', 'Chest', 3, 'RARE', '', 0, 5, 17, 6, 'Icons/Items/Chest/1.png', 2, '2026-09-02 23:53:30', 1),
('dfd60908-71c9-4653-908d-3db90131de62', 'Heavy Helm', 'Helmet', 2, 'UNCOMMON', '', 0, 3, 8, 0, 'Icons/Items/Helmet/4.png', 1, '2026-09-02 23:50:16', 1),
('e015cf5d-1bfc-44bc-bf01-ddbd4fa37c33', 'Ancient Greaves', 'Boots', 1, 'UNCOMMON', '', 0, 1, 4, 0, 'Icons/Items/Boots/4.png', 1, '2026-09-02 23:47:45', 1),
('e51cd5a7-649a-4812-9231-da0d1d5e2b82', 'Sturdy Sabatons', 'Boots', 50, 'RARE', '', 0, 22, 53, 0, 'Icons/Items/Boots/4.png', 1, '2026-09-03 11:57:15', 1),
('e7101bec-4150-4cd7-81f2-fe631b691d4a', 'Enchanted Falchion', 'Weapon', 2, 'RARE', '', 12, 0, 0, 0, 'Icons/Items/Weapon/7.png', 1, '2026-09-02 23:49:26', 1),
('f0f4f250-b3a3-49ba-b179-1e2cfdb00b41', 'Old Headguard', 'Helmet', 1, 'COMMON', '', 0, 2, 6, 0, 'Icons/Items/Helmet/9.png', 0, '2026-09-02 23:53:07', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `item_sockets`
--

CREATE TABLE `item_sockets` (
  `id` int NOT NULL,
  `item_id` varchar(36) NOT NULL,
  `socket_index` int NOT NULL,
  `gem_item_id` varchar(36) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `item_sockets`
--

INSERT INTO `item_sockets` (`id`, `item_id`, `socket_index`, `gem_item_id`, `created_at`) VALUES
(13, 'e7101bec-4150-4cd7-81f2-fe631b691d4a', 0, 'b9c7dee6-95a5-488b-9318-4df4ea31ff0e', '2026-09-03 11:57:28');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `yookassa_payment_id` varchar(64) DEFAULT NULL,
  `user_id` int NOT NULL,
  `product_id` varchar(64) NOT NULL,
  `gold_amount` int NOT NULL,
  `price_rub` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `payments`
--

INSERT INTO `payments` (`id`, `yookassa_payment_id`, `user_id`, `product_id`, `gold_amount`, `price_rub`, `status`, `created_at`) VALUES
(1, NULL, 2, 'gold_1000', 1000, '100.00', 'failed', '2026-09-03 14:09:08');

-- --------------------------------------------------------

--
-- Структура таблицы `progress`
--

CREATE TABLE `progress` (
  `id` int NOT NULL,
  `character_id` int NOT NULL,
  `dungeon_id` varchar(64) NOT NULL,
  `completed` tinyint(1) DEFAULT '0',
  `best_difficulty` int DEFAULT '0',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `login` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `token_expires` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `password_reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `login`, `password_hash`, `auth_token`, `token_expires`, `created_at`, `updated_at`, `password_reset_token`) VALUES
(2, 'jetananas@yandex.ru', '123', '$2y$13$9FIOibkIGp8V3QMn0penb.wrftYxk5eiygnppV5zuZAXLl8c1TRAi', '4eac0ad8e7ad6508e30ba3d1d579645a', NULL, '2026-08-15 19:39:31', '2026-09-03 14:08:55', NULL),
(3, 'ib@yandex.ru', 'test', '$2y$13$pck5niivKGSBau9DRArUXOhTq96vscrTOxrxEbMNmuzxKTPbGYxAS', NULL, NULL, '2026-09-02 23:16:10', '2026-09-02 23:16:10', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `auction`
--
ALTER TABLE `auction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Индексы таблицы `auction_lots`
--
ALTER TABLE `auction_lots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Индексы таблицы `auction_lot_items`
--
ALTER TABLE `auction_lot_items`
  ADD PRIMARY KEY (`lot_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Индексы таблицы `characters`
--
ALTER TABLE `characters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `character_talents`
--
ALTER TABLE `character_talents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_talent_per_character` (`character_id`,`talent_id`);

--
-- Индексы таблицы `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `character_id` (`character_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Индексы таблицы `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `item_sockets`
--
ALTER TABLE `item_sockets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_item_socket` (`item_id`,`socket_index`),
  ADD KEY `idx_gem_item_id` (`gem_item_id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_yookassa_id` (`yookassa_payment_id`);

--
-- Индексы таблицы `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `character_id` (`character_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `idx_password_reset_token` (`password_reset_token`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `auction`
--
ALTER TABLE `auction`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `auction_lots`
--
ALTER TABLE `auction_lots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT для таблицы `characters`
--
ALTER TABLE `characters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `character_talents`
--
ALTER TABLE `character_talents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=448;

--
-- AUTO_INCREMENT для таблицы `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT для таблицы `item_sockets`
--
ALTER TABLE `item_sockets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `auction`
--
ALTER TABLE `auction`
  ADD CONSTRAINT `auction_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auction_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `characters` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `auction_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `auction_lots`
--
ALTER TABLE `auction_lots`
  ADD CONSTRAINT `auction_lots_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `auction_lot_items`
--
ALTER TABLE `auction_lot_items`
  ADD CONSTRAINT `auction_lot_items_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `auction_lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auction_lot_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Ограничения внешнего ключа таблицы `characters`
--
ALTER TABLE `characters`
  ADD CONSTRAINT `characters_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `character_talents`
--
ALTER TABLE `character_talents`
  ADD CONSTRAINT `character_talents_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `item_sockets`
--
ALTER TABLE `item_sockets`
  ADD CONSTRAINT `fk_socket_gem` FOREIGN KEY (`gem_item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_socket_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
