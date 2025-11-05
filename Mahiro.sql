-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 05 2025 г., 13:40
-- Версия сервера: 10.6.9-MariaDB
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `Mahiro`
--

-- --------------------------------------------------------

--
-- Структура таблицы `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `games`
--

INSERT INTO `games` (`id`, `title`, `description`, `price`, `image`, `genre`, `release_date`, `created_at`) VALUES
(1, 'Black souls 2', 'Продолжение мрачной фэнтезийной серии. Исследуйте мир, где смерть — лишь начало. \r\n  Вас ждут динамичные сражения, глубокая система прокачки и жестокие, но справедливые испытания. \r\n  Эта игра потребует терпения, внимательности и решимости пройти через мрак до света.', '2399.00', 'https://cs14.pikabu.ru/post_img/big/2022/11/20/6/1668938021150942305.jpg', NULL, '2009-11-20', '2025-11-04 13:52:04'),
(5, 'Dead Space 4', 'Классический научно-популярный хоррор с элементами выживания возвращается: игра была воссоздана с нуля и предлагает ещё глубже погрузиться в обволакивающую атмосферу мёртвого космоса.', '12000.00', 'https://cdn.wccftech.com/wp-content/uploads/2022/12/WCCFdeadspaceremake8.jpg', NULL, '2023-11-03', '2025-11-05 10:33:17');

-- --------------------------------------------------------

--
-- Структура таблицы `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `purchase_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `purchases`
--

INSERT INTO `purchases` (`id`, `user_id`, `game_id`, `purchase_date`) VALUES
(1, 2, 1, '2025-11-05 10:09:59');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `gmail` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date` timestamp NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT 'default.png',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `card_number_last4` varchar(4) DEFAULT NULL,
  `card_name` varchar(100) DEFAULT NULL,
  `card_exp` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `gmail`, `password`, `date`, `avatar`, `balance`, `card_number_last4`, `card_name`, `card_exp`) VALUES
(1, 'Doctor', 'Doctor@fmail.com', '123321123321', '2025-09-24 12:49:34', 'default.png', '0.00', NULL, NULL, NULL),
(2, 'DoctorRimet', 'Doctosr@fsmail.com', '123321', '2025-10-21 10:35:59', 'uploads/1762335689_wallpaperflare.com_wallpaper (1).jpg', '17901.00', '6666', 'Доктор Раймет', '20/23');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gmail` (`gmail`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
