-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Дек 02 2024 г., 21:52
-- Версия сервера: 5.5.62
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `local_speed`
--

-- --------------------------------------------------------

--
-- Структура таблицы `network_logs`
--

CREATE TABLE `network_logs` (
  `id` int(11) NOT NULL,
  `test_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `download_speed` decimal(10,2) DEFAULT NULL,
  `upload_speed` decimal(10,2) DEFAULT NULL,
  `ping` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `network_logs`
--

INSERT INTO `network_logs` (`id`, `test_time`, `ip_address`, `mac_address`, `hostname`, `download_speed`, `upload_speed`, `ping`, `status`) VALUES
(57, '2024-12-02 18:51:57', '192.168.1.1', '', 'gpon.net', '91.00', '6.00', '77.00', 'online'),
(58, '2024-12-02 18:52:07', '192.168.1.3', NULL, 'Ziyo', '53.00', '6.00', '79.00', 'online');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `network_logs`
--
ALTER TABLE `network_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_time` (`test_time`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_mac_address` (`mac_address`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `network_logs`
--
ALTER TABLE `network_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
