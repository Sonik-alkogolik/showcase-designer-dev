-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Мар 20 2026 г., 06:56
-- Версия сервера: 8.0.45-0ubuntu0.22.04.1
-- Версия PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `showcase_designer`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `shop_id`, `name`, `slug`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Тестовая категория', 'testovaia-kategoriia', NULL, 0, 1, '2026-03-01 10:45:39', '2026-03-01 10:45:39'),
(6, 1, 'Электроника', 'elektronika', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(7, 1, 'Одежда', 'odezda', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(8, 1, 'Продукты', 'produkty', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(9, 1, 'Книги', 'knigi', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(10, 1, 'Обувь', 'obuv', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(11, 1, 'Верхняя одежда', 'verxniaia-odezda', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(12, 1, 'Аксессуары', 'aksessuary', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 11:25:41'),
(13, 1, 'Головные уборы', 'golovnye-ubory', NULL, 0, 1, '2026-03-02 11:25:41', '2026-03-02 16:18:20'),
(14, 1, 'ТЕСТ Добавление категории', 'test-dobavlenie-kategorii', NULL, 0, 1, '2026-03-02 16:18:33', '2026-03-02 16:18:33'),
(15, 1, 'Без категории', 'bez-kategorii', 'Товары без категории', 999999, 1, '2026-03-02 18:43:51', '2026-03-02 18:43:51');

-- --------------------------------------------------------

--
-- Структура таблицы `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_18_111336_create_personal_access_tokens_table', 2),
(5, '2026_01_18_112136_create_personal_access_tokens_table', 1),
(6, '2026_01_19_183428_create_email_verification_tokens_table', 3),
(7, '2026_01_20_155346_add_telegram_fields_to_users_table', 3),
(8, '2026_01_30_124655_fix_telegram_fields_types_in_users_table', 4),
(9, '2026_02_01_140547_add_telegram_fields_to_users_table', 5),
(10, '2026_02_01_141247_update_telegram_fields_for_linking', 5),
(11, '2026_02_19_155325_create_subscriptions_table', 6),
(12, '2020_10_04_115514_create_moonshine_roles_table', 7),
(13, '2020_10_05_173148_create_moonshine_tables', 7),
(14, '2026_02_21_141240_create_shops_table', 8),
(15, '2026_02_22_153905_create_products_table', 9),
(16, '2026_02_23_152904_create_orders_table', 10),
(17, '2026_02_28_085251_add_attributes_to_products_table', 11),
(18, '2026_03_01_103611_create_categories_table', 12),
(19, '2026_03_02_112205_add_category_id_to_products_table', 13),
(20, '2026_03_02_112211_migrate_categories_data', 13),
(21, '2026_03_02_184138_add_default_misc_category', 14);

-- --------------------------------------------------------

--
-- Структура таблицы `moonshine_users`
--

CREATE TABLE `moonshine_users` (
  `id` bigint UNSIGNED NOT NULL,
  `moonshine_user_role_id` bigint UNSIGNED NOT NULL DEFAULT '1',
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `moonshine_users`
--

INSERT INTO `moonshine_users` (`id`, `moonshine_user_role_id`, `email`, `password`, `name`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin4ik_admin', '$2y$12$KzE4kZdRMjptZb5ymLSBsembT2/Y20g.6AaTV4gX2Kp0pLI.YnfnC', 'admin4ik_admin', NULL, NULL, '2026-02-21 08:56:27', '2026-02-21 08:56:27');

-- --------------------------------------------------------

--
-- Структура таблицы `moonshine_user_roles`
--

CREATE TABLE `moonshine_user_roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `moonshine_user_roles`
--

INSERT INTO `moonshine_user_roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '2026-02-21 08:54:28', '2026-02-21 08:54:28');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `delivery_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `yookassa_payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `items` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `shop_id`, `customer_name`, `phone`, `total`, `delivery_name`, `delivery_price`, `status`, `yookassa_payment_id`, `items`, `created_at`, `updated_at`) VALUES
(1, 1, 'Иван Петров', '+70000000000', '700.00', 'Самовывоз', '0.00', 'pending', NULL, '[{\"id\": 7, \"name\": \"Тестовый товар 1\", \"price\": \"100.00\", \"quantity\": 1}, {\"id\": 8, \"name\": \"Тестовый товар 2\", \"price\": \"200.00\", \"quantity\": 3}]', '2026-02-24 08:55:01', '2026-02-24 08:55:01'),
(2, 1, 'Иван Иванов', '+799999999999', '200.00', 'Самовывоз', '0.00', 'pending', NULL, '[{\"id\": 7, \"name\": \"Тестовый товар 1\", \"price\": \"100.00\", \"quantity\": 2}]', '2026-02-25 10:31:15', '2026-02-25 10:31:15'),
(3, 1, 'Иван Иванов', '+7 99999999', '200.00', 'Самовывоз', '0.00', 'pending', NULL, '[{\"id\": 7, \"name\": \"Тестовый товар 1\", \"price\": \"100.00\", \"quantity\": 2}]', '2026-02-25 10:40:49', '2026-02-25 10:40:49'),
(4, 1, 'ыыыыы ыыыы', '11111111111', '300.00', 'Самовывоз', '0.00', 'pending', NULL, '[{\"id\": 7, \"name\": \"Тестовый товар 1\", \"price\": \"100.00\", \"quantity\": 1}, {\"id\": 8, \"name\": \"Тестовый товар 2\", \"price\": \"200.00\", \"quantity\": 1}]', '2026-02-25 10:49:11', '2026-02-25 10:49:11');

-- --------------------------------------------------------

--
-- Структура таблицы `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 'App\\Models\\User', 3, 'auth-token', '118dd2f733ecacb59f1cbdbf1abbfbe926987cf3f3f15351479358ef41315d50', '[\"*\"]', NULL, NULL, '2026-01-18 15:14:05', '2026-01-18 15:14:05'),
(3, 'App\\Models\\User', 5, 'auth-token', '32ec9525fcb4f0bc00bf4760f6310cc92e491d441592170b52f766c198817675', '[\"*\"]', NULL, NULL, '2026-01-20 17:36:22', '2026-01-20 17:36:22'),
(4, 'App\\Models\\User', 5, 'auth-token', 'a587f4e3de36d05ac0b3c9d2be2533ba06fd981be9bdbd0a24cb8c172799a950', '[\"*\"]', NULL, NULL, '2026-01-20 17:36:44', '2026-01-20 17:36:44'),
(5, 'App\\Models\\User', 5, 'auth-token', '0e1d22e48b0bbda184042656983eebe570858e7dba617e6bd5aa5aedb8feb983', '[\"*\"]', NULL, NULL, '2026-01-20 17:43:13', '2026-01-20 17:43:13'),
(6, 'App\\Models\\User', 5, 'auth-token', '112f3fb34c110a844051cd55d1df76888cceebee12c083a54f1eac3eceacb580', '[\"*\"]', NULL, NULL, '2026-01-20 17:45:07', '2026-01-20 17:45:07'),
(7, 'App\\Models\\User', 1, 'debug', '02c2c0095777ecd8b0a3c99339a1ca45c50d78be68ee8289b19a277f62d2447c', '[\"*\"]', NULL, NULL, '2026-01-25 17:28:04', '2026-01-25 17:28:04'),
(8, 'App\\Models\\User', 1, 'debug', 'd1cffd86a2088754a145efdbd604b70afd3e299c7afa46b5ce2fab2870eda982', '[\"*\"]', NULL, NULL, '2026-01-25 17:28:25', '2026-01-25 17:28:25'),
(9, 'App\\Models\\User', 1, 'debug', '1ae59dc4d301d8af8b876265e5960d7cb7705dbd950b490829f832cb14d59391', '[\"*\"]', NULL, NULL, '2026-01-25 17:28:58', '2026-01-25 17:28:58'),
(10, 'App\\Models\\User', 1, 'debug', '0900022fde4b49bd8d44d8e79a4b84d66633b82de9d0cf4284bc556e60064e36', '[\"*\"]', NULL, NULL, '2026-01-25 17:29:02', '2026-01-25 17:29:02'),
(11, 'App\\Models\\User', 1, 'debug', 'fb29442dd5a9821f359912e50cdae284a4c4411d1c69f62eae552024620a7944', '[\"*\"]', NULL, NULL, '2026-01-25 17:29:27', '2026-01-25 17:29:27'),
(12, 'App\\Models\\User', 1, 'debug', '94f7ee965badf5b5464f3745b43d33bafebcf70f1250a96960a567306c5339fd', '[\"*\"]', NULL, NULL, '2026-01-25 17:29:37', '2026-01-25 17:29:37'),
(13, 'App\\Models\\User', 1, 'debug', 'ac80a30e06799349d07452039761f9bfa481eedb229a1c28eba97e848829173e', '[\"*\"]', NULL, NULL, '2026-01-25 17:29:42', '2026-01-25 17:29:42'),
(14, 'App\\Models\\User', 5, 'auth-token', '917d9aeeacdff654fa864f70bb264d9ed17569e7431edcf1f75907896176d0bd', '[\"*\"]', NULL, NULL, '2026-01-25 17:59:48', '2026-01-25 17:59:48'),
(15, 'App\\Models\\User', 5, 'auth-token', '6ed7b6d02cb0b1fa21bcef6a892c395d0e03a20eb2b2a3130bc73e95e47f8ac3', '[\"*\"]', NULL, NULL, '2026-01-25 18:02:56', '2026-01-25 18:02:56'),
(16, 'App\\Models\\User', 5, 'auth-token', '986d8e6748ef31289e96be133cd0e9bd7db8974516ffc5e5036016c4a2de3ecb', '[\"*\"]', NULL, NULL, '2026-01-25 18:18:04', '2026-01-25 18:18:04'),
(17, 'App\\Models\\User', 5, 'auth-token', '45ddcd72515813ad81afcd52a94c931422b8005591d9831ba1c86fcf236befcb', '[\"*\"]', NULL, NULL, '2026-01-25 18:18:31', '2026-01-25 18:18:31'),
(18, 'App\\Models\\User', 5, 'auth-token', '11e58f6ff5dd9bb697eaccc81c80a9af02029624d7bd1a0acf7170b05b6bf111', '[\"*\"]', NULL, NULL, '2026-01-25 18:20:38', '2026-01-25 18:20:38'),
(19, 'App\\Models\\User', 5, 'auth-token', '24f91cec1b56978b6906b4e1e55cfdf4744509b38e4c63f9a4e5370862a642b6', '[\"*\"]', NULL, NULL, '2026-01-30 11:20:58', '2026-01-30 11:20:58'),
(20, 'App\\Models\\User', 5, 'auth-token', '451e6e0706a88588bbbe920a7ad4f57f8eb66303cc593f74ac8990164cc27258', '[\"*\"]', NULL, NULL, '2026-01-30 11:26:58', '2026-01-30 11:26:58'),
(21, 'App\\Models\\User', 5, 'auth-token', '284e67f28930edfed90eaae02c579540b9a0c5f57aa79ca7b2a1607a4019c94f', '[\"*\"]', NULL, NULL, '2026-01-30 11:51:08', '2026-01-30 11:51:08'),
(22, 'App\\Models\\User', 5, 'auth-token', '1ad6794e0fd5b05519c0929f8eb73e3e10f5b23acf70b34ada0ea67863b53f81', '[\"*\"]', NULL, NULL, '2026-01-30 11:52:48', '2026-01-30 11:52:48'),
(23, 'App\\Models\\User', 5, 'auth-token', '585814fa8566eb6e95ce60464d53960298c30f4389f3f400d244a6792c3e1259', '[\"*\"]', NULL, NULL, '2026-01-30 11:55:42', '2026-01-30 11:55:42'),
(24, 'App\\Models\\User', 5, 'auth-token', '24e07093e37a83d973ce7c4a838c0903f4f9dae51054d91d15dfa40f64034113', '[\"*\"]', NULL, NULL, '2026-01-30 12:12:19', '2026-01-30 12:12:19'),
(25, 'App\\Models\\User', 5, 'auth-token', '6efd89ee371d4608a04255d3099534510fb30da36b6f8dd77c47a4d0b0b1e1c3', '[\"*\"]', NULL, NULL, '2026-01-30 12:12:19', '2026-01-30 12:12:19'),
(26, 'App\\Models\\User', 5, 'auth-token', 'e5b921992175dee575fe682ab2fdc1213590e10c441ab4f4686dbb56f0ee7b03', '[\"*\"]', NULL, NULL, '2026-01-30 12:12:33', '2026-01-30 12:12:33'),
(27, 'App\\Models\\User', 6, 'auth-token', 'b28114f3a8a141215bc0ca2d09133f3365b9329cc3c4a6e283e30990cecb70fc', '[\"*\"]', '2026-01-30 13:21:55', NULL, '2026-01-30 13:19:24', '2026-01-30 13:21:55'),
(28, 'App\\Models\\User', 7, 'auth-token', '8bcc1433435a5b9a7f586b1693ed31d332a35005a1f6c8af24c6ab77234b4e84', '[\"*\"]', NULL, NULL, '2026-01-30 14:22:03', '2026-01-30 14:22:03'),
(30, 'App\\Models\\User', 7, 'auth-token', 'e627b2f3649440696f2dd040421e0de12a9e88b5401dd759615df9456ecb3a8e', '[\"*\"]', '2026-02-01 09:56:50', NULL, '2026-02-01 09:56:11', '2026-02-01 09:56:50'),
(31, 'App\\Models\\User', 8, 'auth-token', '6e762ea88820a19ef9e36aa9de6eec496ec29eb627f58f4b33f2ef2a37bd971f', '[\"*\"]', '2026-02-01 10:06:25', NULL, '2026-02-01 09:58:50', '2026-02-01 10:06:25'),
(32, 'App\\Models\\User', 9, 'auth-token', 'f980eaf69fe5179110097a04f0a4f345aafc08150c49a58bc1bf9f8425c38b72', '[\"*\"]', '2026-02-01 10:45:38', NULL, '2026-02-01 10:42:23', '2026-02-01 10:45:38'),
(33, 'App\\Models\\User', 10, 'auth-token', 'b620ccdae7e20015983340580ff5c7b6f0f3c4c262786934d7b5530ee138d9ab', '[\"*\"]', '2026-02-01 16:25:42', NULL, '2026-02-01 16:23:38', '2026-02-01 16:25:42'),
(34, 'App\\Models\\User', 11, 'auth-token', '8a044e664fd735d8525862aa551bc1001cbbd09e76a34d1230bec5df580f3117', '[\"*\"]', '2026-03-10 16:06:40', NULL, '2026-02-01 17:02:26', '2026-03-10 16:06:40'),
(35, 'App\\Models\\User', 11, 'auth-token', '1529c3a99c8c8817d88bc11264eec3d4400de45a645a2188a221eb4ee1e2c8de', '[\"*\"]', '2026-03-03 15:29:06', NULL, '2026-02-19 18:30:21', '2026-03-03 15:29:06');

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `in_stock` tinyint(1) NOT NULL DEFAULT '1',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `shop_id`, `category_id`, `name`, `price`, `description`, `category`, `in_stock`, `image`, `attributes`, `created_at`, `updated_at`) VALUES
(12, 1, NULL, 'Òåñòîâûé òîâàð 1', '100.00', 'Îïèñàíèå òîâàðà 1', 'Ýëåêòðîíèêà', 1, NULL, NULL, '2026-02-27 10:32:03', '2026-03-02 18:25:53'),
(13, 1, NULL, 'Òåñòîâûé òîâàð 2', '200.00', 'Îïèñàíèå òîâàðà 2', 'Îäåæäà', 1, NULL, NULL, '2026-02-27 10:32:03', '2026-03-02 18:25:49'),
(14, 1, NULL, 'Òåñòîâûé òîâàð 3', '300.00', NULL, 'Ïðîäóêòû', 1, NULL, NULL, '2026-02-27 10:32:03', '2026-03-02 18:25:51'),
(15, 1, NULL, 'Òåñòîâûé òîâàð 4', '400.00', 'Òîâàð ñ îïèñàíèåì', 'Êíèãè', 1, NULL, NULL, '2026-02-27 10:32:03', '2026-03-02 18:25:46'),
(16, 1, NULL, 'Òåñòîâûé òîâàð 5', '500.00', NULL, NULL, 1, NULL, NULL, '2026-02-27 10:32:03', '2026-02-27 10:32:03'),
(17, 1, NULL, 'Òåñòîâûé òîâàð 1', '100.00', 'Îïèñàíèå òîâàðà 1', 'Ýëåêòðîíèêà', 1, NULL, NULL, '2026-02-27 10:36:22', '2026-03-02 18:25:53'),
(18, 1, NULL, 'Òåñòîâûé òîâàð 2', '200.00', 'Îïèñàíèå òîâàðà 2', 'Îäåæäà', 1, NULL, NULL, '2026-02-27 10:36:22', '2026-03-02 18:25:49'),
(19, 1, NULL, 'Òåñòîâûé òîâàð 3', '300.00', NULL, 'Ïðîäóêòû', 1, NULL, NULL, '2026-02-27 10:36:22', '2026-03-02 18:25:51'),
(20, 1, NULL, 'Òåñòîâûé òîâàð 4', '400.00', 'Òîâàð ñ îïèñàíèåì', 'Êíèãè', 1, NULL, NULL, '2026-02-27 10:36:22', '2026-03-02 18:25:46'),
(21, 1, NULL, 'Òåñòîâûé òîâàð 5', '500.00', NULL, NULL, 1, NULL, NULL, '2026-02-27 10:36:22', '2026-02-27 10:36:22'),
(22, 1, NULL, 'Òåñòîâûé òîâàð 1', '100.00', 'Îïèñàíèå òîâàðà 1', 'Ýëåêòðîíèêà', 1, NULL, NULL, '2026-02-27 10:38:51', '2026-03-02 18:25:53'),
(23, 1, NULL, 'Òåñòîâûé òîâàð 2', '200.00', 'Îïèñàíèå òîâàðà 2', 'Îäåæäà', 1, NULL, NULL, '2026-02-27 10:38:51', '2026-03-02 18:25:49'),
(24, 1, NULL, 'Òåñòîâûé òîâàð 3', '300.00', NULL, 'Ïðîäóêòû', 1, NULL, NULL, '2026-02-27 10:38:51', '2026-03-02 18:25:51'),
(25, 1, NULL, 'Òåñòîâûé òîâàð 4', '400.00', 'Òîâàð ñ îïèñàíèåì', 'Êíèãè', 1, NULL, NULL, '2026-02-27 10:38:51', '2026-03-02 18:25:46'),
(26, 1, NULL, 'Òåñòîâûé òîâàð 5', '500.00', NULL, NULL, 1, NULL, NULL, '2026-02-27 10:38:51', '2026-02-27 10:38:51'),
(27, 1, 6, 'Тестовый товар 1', '100.00', 'Описание товара 1', 'Электроника', 1, NULL, NULL, '2026-02-27 10:45:37', '2026-02-27 10:45:37'),
(28, 1, 7, 'Тестовый товар 2', '200.00', 'Описание товара 2', 'Одежда', 1, NULL, NULL, '2026-02-27 10:45:37', '2026-02-27 10:45:37'),
(29, 1, 8, 'Тестовый товар 3', '300.00', NULL, 'Продукты', 1, NULL, NULL, '2026-02-27 10:45:37', '2026-02-27 10:45:37'),
(30, 1, 9, 'Тестовый товар 4', '400.00', 'Товар с описанием', 'Книги', 1, NULL, NULL, '2026-02-27 10:45:37', '2026-02-27 10:45:37'),
(31, 1, NULL, 'Тестовый товар 5', '500.00', NULL, NULL, 1, NULL, NULL, '2026-02-27 10:45:37', '2026-02-27 10:45:37'),
(32, 1, 6, 'Тестовый товар 1', '100.00', 'Описание товара 1', 'Электроника', 1, NULL, NULL, '2026-02-27 10:48:57', '2026-02-27 10:48:57'),
(33, 1, 7, 'Тестовый товар 2', '200.00', 'Описание товара 2', 'Одежда', 1, NULL, NULL, '2026-02-27 10:48:57', '2026-02-27 10:48:57'),
(34, 1, 8, 'Тестовый товар 3', '300.00', NULL, 'Продукты', 1, NULL, NULL, '2026-02-27 10:48:57', '2026-02-27 10:48:57'),
(35, 1, 9, 'Тестовый товар 4', '400.00', 'Товар с описанием', 'Книги', 1, NULL, NULL, '2026-02-27 10:48:57', '2026-02-27 10:48:57'),
(36, 1, NULL, 'Тестовый товар 5', '500.00', NULL, NULL, 1, NULL, NULL, '2026-02-27 10:48:57', '2026-02-27 10:48:57'),
(37, 1, NULL, 'Тестовый товар', '100.00', NULL, NULL, 1, NULL, '{\"size\": \"XL\", \"color\": \"red\"}', '2026-02-28 08:57:06', '2026-02-28 08:57:06'),
(38, 1, 7, 'Футболка базовая', '890.00', 'Базовая хлопковая футболка', 'Одежда', 0, NULL, '{\"care\": \"деликатная стирка\", \"size\": \"S\", \"brand\": \"Uniqlo\", \"color\": \"белый\", \"rating\": 4.5, \"season\": \"лето\", \"weight\": 0.2, \"country\": \"Бангладеш\", \"material\": \"хлопок\", \"collection\": \"Basic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(39, 1, 7, 'Футболка базовая', '890.00', 'Базовая хлопковая футболка', 'Одежда', 0, NULL, '{\"care\": \"деликатная стирка\", \"size\": \"M\", \"brand\": \"Uniqlo\", \"color\": \"белый\", \"rating\": 4.5, \"season\": \"лето\", \"weight\": 0.2, \"country\": \"Бангладеш\", \"material\": \"хлопок\", \"collection\": \"Basic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(40, 1, 7, 'Футболка базовая', '890.00', 'Базовая хлопковая футболка', 'Одежда', 0, NULL, '{\"care\": \"деликатная стирка\", \"size\": \"L\", \"brand\": \"Uniqlo\", \"color\": \"белый\", \"rating\": 4.5, \"season\": \"лето\", \"weight\": 0.2, \"country\": \"Бангладеш\", \"material\": \"хлопок\", \"collection\": \"Basic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(41, 1, 7, 'Футболка базовая', '890.00', 'Базовая хлопковая футболка', 'Одежда', 0, NULL, '{\"care\": \"деликатная стирка\", \"size\": \"S\", \"brand\": \"Uniqlo\", \"color\": \"черный\", \"rating\": 4.5, \"season\": \"лето\", \"weight\": 0.2, \"country\": \"Бангладеш\", \"material\": \"хлопок\", \"collection\": \"Basic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(42, 1, 7, 'Футболка базовая', '890.00', 'Базовая хлопковая футболка', 'Одежда', 0, NULL, '{\"care\": \"деликатная стирка\", \"size\": \"M\", \"brand\": \"Uniqlo\", \"color\": \"черный\", \"rating\": 4.5, \"season\": \"лето\", \"weight\": 0.2, \"country\": \"Бангладеш\", \"material\": \"хлопок\", \"collection\": \"Basic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(43, 1, 7, 'Джинсы классические', '2990.00', 'Классические джинсы из плотного хлопка', 'Одежда', 0, NULL, '{\"care\": \"машинная стирка 30°\", \"size\": 30, \"brand\": \"Levi\'s\", \"color\": \"синий\", \"rating\": 4.8, \"season\": \"всесезон\", \"weight\": 0.8, \"country\": \"США\", \"discount\": 15, \"material\": \"деним\", \"collection\": \"Premium\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(44, 1, 7, 'Джинсы классические', '2990.00', 'Классические джинсы из плотного хлопка', 'Одежда', 0, NULL, '{\"care\": \"машинная стирка 30°\", \"size\": 31, \"brand\": \"Levi\'s\", \"color\": \"синий\", \"rating\": 4.8, \"season\": \"всесезон\", \"weight\": 0.8, \"country\": \"США\", \"discount\": 15, \"material\": \"деним\", \"collection\": \"Premium\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(45, 1, 7, 'Джинсы классические', '2990.00', 'Классические джинсы из плотного хлопка', 'Одежда', 0, NULL, '{\"care\": \"машинная стирка 30°\", \"size\": 32, \"brand\": \"Levi\'s\", \"color\": \"синий\", \"rating\": 4.8, \"season\": \"всесезон\", \"weight\": 0.8, \"country\": \"США\", \"discount\": 15, \"material\": \"деним\", \"collection\": \"Premium\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(46, 1, 10, 'Кроссовки беговые', '5490.00', 'Легкие кроссовки для бега', 'Обувь', 0, NULL, '{\"care\": \"ручная стирка\", \"size\": 42, \"brand\": \"Nike\", \"color\": \"черный/белый\", \"rating\": 4.7, \"season\": \"весна-осень\", \"weight\": 0.4, \"country\": \"Вьетнам\", \"discount\": 10, \"material\": \"текстиль/резина\", \"collection\": \"Air Max\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(47, 1, 10, 'Кроссовки беговые', '5490.00', 'Легкие кроссовки для бега', 'Обувь', 1, NULL, '{\"care\": \"ручная стирка\", \"size\": 43, \"brand\": \"Nike\", \"color\": \"черный/белый\", \"rating\": 4.7, \"season\": \"весна-осень\", \"weight\": 0.4, \"country\": \"Вьетнам\", \"discount\": 10, \"material\": \"текстиль/резина\", \"collection\": \"Air Max\"}', '2026-02-28 09:42:37', '2026-02-28 09:43:04'),
(48, 1, 10, 'Кроссовки беговые', '5490.00', 'Легкие кроссовки для бега', 'Обувь', 0, NULL, '{\"care\": \"ручная стирка\", \"size\": 44, \"brand\": \"Nike\", \"color\": \"черный/белый\", \"rating\": 4.7, \"season\": \"весна-осень\", \"weight\": 0.4, \"country\": \"Вьетнам\", \"discount\": 10, \"material\": \"текстиль/резина\", \"collection\": \"Air Max\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(49, 1, 11, 'Куртка пуховая', '12990.00', 'Теплая зимняя куртка', 'Верхняя одежда', 0, NULL, '{\"care\": \"химчистка\", \"size\": \"L\", \"brand\": \"The North Face\", \"color\": \"темно-синий\", \"rating\": 4.9, \"season\": \"зима\", \"weight\": 1.2, \"country\": \"Китай\", \"discount\": 20, \"material\": \"нейлон/пух\", \"collection\": \"Arctic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(50, 1, 11, 'Куртка пуховая', '12990.00', 'Теплая зимняя куртка', 'Верхняя одежда', 0, NULL, '{\"care\": \"химчистка\", \"size\": \"XL\", \"brand\": \"The North Face\", \"color\": \"темно-синий\", \"rating\": 4.9, \"season\": \"зима\", \"weight\": 1.3, \"country\": \"Китай\", \"discount\": 20, \"material\": \"нейлон/пух\", \"collection\": \"Arctic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(51, 1, 11, 'Куртка пуховая', '12990.00', 'Теплая зимняя куртка', 'Верхняя одежда', 0, NULL, '{\"care\": \"химчистка\", \"size\": \"L\", \"brand\": \"The North Face\", \"color\": \"черный\", \"rating\": 4.9, \"season\": \"зима\", \"weight\": 1.2, \"country\": \"Китай\", \"discount\": 20, \"material\": \"нейлон/пух\", \"collection\": \"Arctic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(52, 1, 11, 'Куртка пуховая', '12990.00', 'Теплая зимняя куртка', 'Верхняя одежда', 0, NULL, '{\"care\": \"химчистка\", \"size\": \"XL\", \"brand\": \"The North Face\", \"color\": \"черный\", \"rating\": 4.9, \"season\": \"зима\", \"weight\": 1.3, \"country\": \"Китай\", \"discount\": 20, \"material\": \"нейлон/пух\", \"collection\": \"Arctic\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(53, 1, 12, 'Рюкзак городской', '3490.00', 'Рюкзак для ноутбука 15\"', 'Аксессуары', 0, NULL, '{\"care\": \"не стирать\", \"size\": \"универсальный\", \"brand\": \"Xiaomi\", \"color\": \"серый\", \"rating\": 4.6, \"season\": \"всесезон\", \"weight\": 0.6, \"country\": \"Китай\", \"material\": \"полиэстер\", \"collection\": \"Urban\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(54, 1, 12, 'Рюкзак городской', '3490.00', 'Рюкзак для ноутбука 15\"', 'Аксессуары', 0, NULL, '{\"care\": \"не стирать\", \"size\": \"универсальный\", \"brand\": \"Xiaomi\", \"color\": \"черный\", \"rating\": 4.6, \"season\": \"всесезон\", \"weight\": 0.6, \"country\": \"Китай\", \"material\": \"полиэстер\", \"collection\": \"Urban\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(55, 1, 13, 'Шапка вязаная', '890.00', 'Теплая вязаная шапка', 'Головные уборы', 0, NULL, '{\"care\": \"ручная стирка\", \"size\": \"56-58\", \"brand\": \"Adidas\", \"color\": \"бежевый\", \"rating\": 4.4, \"season\": \"зима\", \"weight\": 0.1, \"country\": \"Россия\", \"material\": \"шерсть/акрил\", \"collection\": \"Originals\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(56, 1, 13, 'Шапка вязаная', '890.00', 'Теплая вязаная шапка', 'Головные уборы', 0, NULL, '{\"care\": \"ручная стирка\", \"size\": \"56-58\", \"brand\": \"Adidas\", \"color\": \"серый\", \"rating\": 4.4, \"season\": \"зима\", \"weight\": 0.1, \"country\": \"Россия\", \"material\": \"шерсть/акрил\", \"collection\": \"Originals\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(57, 1, 12, 'Носки хлопковые', '290.00', 'Набор 3 пары', 'Аксессуары', 0, NULL, '{\"care\": \"машинная стирка\", \"size\": \"39-42\", \"brand\": \"Nike\", \"color\": \"черный/белый\", \"rating\": 4.3, \"season\": \"всесезон\", \"weight\": 0.1, \"country\": \"Турция\", \"material\": \"хлопок\", \"collection\": \"Sport\"}', '2026-02-28 09:42:37', '2026-02-28 09:42:37'),
(58, 1, NULL, 'Смартфон iPhone 15 Pro', '99990.00', NULL, NULL, 1, NULL, '{\"os\": \"iOS 17\", \"ram\": 8, \"brand\": \"Apple\", \"color\": \"черный\", \"model\": \"iPhone 15 Pro\", \"rating\": 4.8, \"battery\": 3274, \"storage\": 256, \"processor\": \"A17 Pro\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(59, 1, NULL, 'Смартфон iPhone 15 Pro', '99990.00', NULL, NULL, 1, NULL, '{\"os\": \"iOS 17\", \"ram\": 8, \"brand\": \"Apple\", \"color\": \"синий\", \"model\": \"iPhone 15 Pro\", \"rating\": 4.8, \"battery\": 3274, \"storage\": 512, \"processor\": \"A17 Pro\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(60, 1, NULL, 'Смартфон iPhone 15 Pro', '109990.00', NULL, NULL, 1, NULL, '{\"os\": \"iOS 17\", \"ram\": 8, \"brand\": \"Apple\", \"color\": \"титановый\", \"model\": \"iPhone 15 Pro\", \"rating\": 4.8, \"battery\": 3274, \"storage\": 1024, \"processor\": \"A17 Pro\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(61, 1, NULL, 'Смартфон Galaxy S24', '89990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 12, \"brand\": \"Samsung\", \"color\": \"черный\", \"model\": \"Samsung S24\", \"rating\": 4.7, \"battery\": 4000, \"storage\": 256, \"processor\": \"Exynos 2400\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(62, 1, NULL, 'Смартфон Galaxy S24', '89990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 12, \"brand\": \"Samsung\", \"color\": \"серый\", \"model\": \"Samsung S24\", \"rating\": 4.7, \"battery\": 4000, \"storage\": 512, \"processor\": \"Exynos 2400\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(63, 1, NULL, 'Смартфон Galaxy S24', '99990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 12, \"brand\": \"Samsung\", \"color\": \"фиолетовый\", \"model\": \"Samsung S24\", \"rating\": 4.7, \"battery\": 4000, \"storage\": 1024, \"processor\": \"Exynos 2400\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(64, 1, NULL, 'Смартфон Xiaomi 14 Pro', '69990.00', NULL, NULL, 1, NULL, '{\"os\": \"HyperOS 1.0\", \"ram\": 16, \"brand\": \"Xiaomi\", \"color\": \"черный\", \"model\": \"Xiaomi 14 Pro\", \"rating\": 4.6, \"battery\": 4880, \"storage\": 256, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(65, 1, NULL, 'Смартфон Xiaomi 14 Pro', '69990.00', NULL, NULL, 1, NULL, '{\"os\": \"HyperOS 1.0\", \"ram\": 16, \"brand\": \"Xiaomi\", \"color\": \"зеленый\", \"model\": \"Xiaomi 14 Pro\", \"rating\": 4.6, \"battery\": 4880, \"storage\": 512, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(66, 1, NULL, 'Смартфон Xiaomi 14 Pro', '79990.00', NULL, NULL, 1, NULL, '{\"os\": \"HyperOS 1.0\", \"ram\": 16, \"brand\": \"Xiaomi\", \"color\": \"титановый\", \"model\": \"Xiaomi 14 Pro\", \"rating\": 4.6, \"battery\": 4880, \"storage\": 1024, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(67, 1, NULL, 'Смартфон Pixel 8 Pro', '79990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": \"8ы\", \"brand\": \"Google\", \"color\": \"черныйВ\", \"model\": \"Google Pixel 8ы\", \"rating\": 4.5, \"battery\": 5050, \"storage\": \"128ы\", \"вввв\": \"ыыыыыыыыффф\", \"processor\": \"Tensor G3\", \"release_year\": \"202ы4\"}', '2026-02-28 09:52:44', '2026-03-01 17:04:47'),
(68, 1, NULL, 'Смартфон Pixel 8 Pro', '79990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 8, \"brand\": \"Google\", \"color\": \"белый\", \"model\": \"Google Pixel 8\", \"rating\": 4.5, \"battery\": 5050, \"storage\": 256, \"processor\": \"Tensor G3\", \"release_year\": 2024}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(69, 1, NULL, 'Смартфон Pixel 8 Pro', '89990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 8, \"brand\": \"Google\", \"color\": \"розовый\", \"model\": \"Google Pixel 8\", \"rating\": 4.5, \"battery\": 5050, \"storage\": 512, \"processor\": \"Tensor G3\", \"release_year\": 2024}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(70, 1, NULL, 'Смартфон OnePlus 12', '64990.00', NULL, NULL, 1, NULL, '{\"os\": \"OxygenOS 14\", \"ram\": 16, \"brand\": \"OnePlus\", \"color\": \"зеленый\", \"model\": \"OnePlus 12\", \"rating\": 4.4, \"battery\": 5400, \"storage\": 256, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(71, 1, NULL, 'Смартфон OnePlus 12', '64990.00', NULL, NULL, 1, NULL, '{\"os\": \"OxygenOS 14\", \"ram\": 16, \"brand\": \"OnePlus\", \"color\": \"черный\", \"model\": \"OnePlus 12\", \"rating\": 4.4, \"battery\": 5400, \"storage\": 512, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(72, 1, NULL, 'Смартфон OnePlus 12', '74990.00', NULL, NULL, 1, NULL, '{\"os\": \"OxygenOS 14\", \"ram\": 16, \"brand\": \"OnePlus\", \"color\": \"серебристый\", \"model\": \"OnePlus 12\", \"rating\": 4.4, \"battery\": 5400, \"storage\": 1024, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(73, 1, NULL, 'Смартфон Magic 6 Pro', '74990.00', NULL, NULL, 1, NULL, '{\"os\": \"MagicOS 8.0\", \"ram\": 12, \"brand\": \"Honor\", \"color\": \"черный\", \"model\": \"Honor Magic 6 Pro\", \"rating\": 4.3, \"battery\": 5600, \"storage\": 256, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(74, 1, NULL, 'Смартфон Magic 6 Pro', '74990.00', NULL, NULL, 1, NULL, '{\"os\": \"MagicOS 8.0\", \"ram\": 12, \"brand\": \"Honor\", \"color\": \"синий\", \"model\": \"Honor Magic 6 Pro\", \"rating\": 4.3, \"battery\": 5600, \"storage\": 512, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(75, 1, NULL, 'Смартфон Magic 6 Pro', '84990.00', NULL, NULL, 1, NULL, '{\"os\": \"MagicOS 8.0\", \"ram\": 12, \"brand\": \"Honor\", \"color\": \"фиолетовый\", \"model\": \"Honor Magic 6 Pro\", \"rating\": 4.3, \"battery\": 5600, \"storage\": 1024, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(76, 1, NULL, 'Смартфон Phone 2', '54990.00', NULL, NULL, 1, NULL, '{\"os\": \"Nothing OS 2.5\", \"ram\": 12, \"brand\": \"Nothing\", \"color\": \"белый\", \"model\": \"Nothing Phone 2\", \"rating\": 4.2, \"battery\": 4700, \"storage\": 256, \"processor\": \"Snapdragon 8+ Gen 1\", \"release_year\": 2024}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(77, 1, NULL, 'Смартфон Phone 2', '54990.00', NULL, NULL, 1, NULL, '{\"os\": \"Nothing OS 2.5\", \"ram\": 12, \"brand\": \"Nothing\", \"color\": \"черный\", \"model\": \"Nothing Phone 2\", \"rating\": 4.2, \"battery\": 4700, \"storage\": 512, \"processor\": \"Snapdragon 8+ Gen 1\", \"release_year\": 2024}', '2026-02-28 09:52:44', '2026-02-28 09:52:44'),
(78, 1, 15, 'sss', '213.00', 'ssssssssss', NULL, 1, NULL, '{}', '2026-03-02 19:08:39', '2026-03-02 19:08:39'),
(79, 1, 15, 'Смартфон iPhone 15 Pro', '99990.00', NULL, NULL, 1, NULL, '{\"os\": \"iOS 17\", \"ram\": 8, \"brand\": \"Apple\", \"color\": \"черный\", \"model\": \"iPhone 15 Pro\", \"rating\": 4.8, \"battery\": 3274, \"storage\": 256, \"processor\": \"A17 Pro\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(80, 1, 15, 'Смартфон iPhone 15 Pro', '99990.00', NULL, NULL, 1, NULL, '{\"os\": \"iOS 17\", \"ram\": 8, \"brand\": \"Apple\", \"color\": \"синий\", \"model\": \"iPhone 15 Pro\", \"rating\": 4.8, \"battery\": 3274, \"storage\": 512, \"processor\": \"A17 Pro\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(81, 1, 15, 'Смартфон iPhone 15 Pro', '109990.00', NULL, NULL, 1, NULL, '{\"os\": \"iOS 17\", \"ram\": 8, \"brand\": \"Apple\", \"color\": \"титановый\", \"model\": \"iPhone 15 Pro\", \"rating\": 4.8, \"battery\": 3274, \"storage\": 1024, \"processor\": \"A17 Pro\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(82, 1, 15, 'Смартфон Galaxy S24', '89990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 12, \"brand\": \"Samsung\", \"color\": \"черный\", \"model\": \"Samsung S24\", \"rating\": 4.7, \"battery\": 4000, \"storage\": 256, \"processor\": \"Exynos 2400\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(83, 1, 15, 'Смартфон Galaxy S24', '89990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 12, \"brand\": \"Samsung\", \"color\": \"серый\", \"model\": \"Samsung S24\", \"rating\": 4.7, \"battery\": 4000, \"storage\": 512, \"processor\": \"Exynos 2400\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(84, 1, 15, 'Смартфон Galaxy S24', '99990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 12, \"brand\": \"Samsung\", \"color\": \"фиолетовый\", \"model\": \"Samsung S24\", \"rating\": 4.7, \"battery\": 4000, \"storage\": 1024, \"processor\": \"Exynos 2400\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(85, 1, 15, 'Смартфон Xiaomi 14 Pro', '69990.00', NULL, NULL, 1, NULL, '{\"os\": \"HyperOS 1.0\", \"ram\": 16, \"brand\": \"Xiaomi\", \"color\": \"черный\", \"model\": \"Xiaomi 14 Pro\", \"rating\": 4.6, \"battery\": 4880, \"storage\": 256, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(86, 1, 15, 'Смартфон Xiaomi 14 Pro', '69990.00', NULL, NULL, 1, NULL, '{\"os\": \"HyperOS 1.0\", \"ram\": 16, \"brand\": \"Xiaomi\", \"color\": \"зеленый\", \"model\": \"Xiaomi 14 Pro\", \"rating\": 4.6, \"battery\": 4880, \"storage\": 512, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(87, 1, 15, 'Смартфон Xiaomi 14 Pro', '79990.00', NULL, NULL, 1, NULL, '{\"os\": \"HyperOS 1.0\", \"ram\": 16, \"brand\": \"Xiaomi\", \"color\": \"титановый\", \"model\": \"Xiaomi 14 Pro\", \"rating\": 4.6, \"battery\": 4880, \"storage\": 1024, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(88, 1, 15, 'Смартфон Pixel 8 Pro', '79990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 8, \"brand\": \"Google\", \"color\": \"черный\", \"model\": \"Google Pixel 8\", \"rating\": 4.5, \"battery\": 5050, \"storage\": 128, \"processor\": \"Tensor G3\", \"release_year\": 2024}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(89, 1, 15, 'Смартфон Pixel 8 Pro', '79990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 8, \"brand\": \"Google\", \"color\": \"белый\", \"model\": \"Google Pixel 8\", \"rating\": 4.5, \"battery\": 5050, \"storage\": 256, \"processor\": \"Tensor G3\", \"release_year\": 2024}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(90, 1, 15, 'Смартфон Pixel 8 Pro', '89990.00', NULL, NULL, 1, NULL, '{\"os\": \"Android 14\", \"ram\": 8, \"brand\": \"Google\", \"color\": \"розовый\", \"model\": \"Google Pixel 8\", \"rating\": 4.5, \"battery\": 5050, \"storage\": 512, \"processor\": \"Tensor G3\", \"release_year\": 2024}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(91, 1, 15, 'Смартфон OnePlus 12', '64990.00', NULL, NULL, 1, NULL, '{\"os\": \"OxygenOS 14\", \"ram\": 16, \"brand\": \"OnePlus\", \"color\": \"зеленый\", \"model\": \"OnePlus 12\", \"rating\": 4.4, \"battery\": 5400, \"storage\": 256, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(92, 1, 15, 'Смартфон OnePlus 12', '64990.00', NULL, NULL, 1, NULL, '{\"os\": \"OxygenOS 14\", \"ram\": 16, \"brand\": \"OnePlus\", \"color\": \"черный\", \"model\": \"OnePlus 12\", \"rating\": 4.4, \"battery\": 5400, \"storage\": 512, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(93, 1, 15, 'Смартфон OnePlus 12', '74990.00', NULL, NULL, 1, NULL, '{\"os\": \"OxygenOS 14\", \"ram\": 16, \"brand\": \"OnePlus\", \"color\": \"серебристый\", \"model\": \"OnePlus 12\", \"rating\": 4.4, \"battery\": 5400, \"storage\": 1024, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(94, 1, 15, 'Смартфон Magic 6 Pro', '74990.00', NULL, NULL, 1, NULL, '{\"os\": \"MagicOS 8.0\", \"ram\": 12, \"brand\": \"Honor\", \"color\": \"черный\", \"model\": \"Honor Magic 6 Pro\", \"rating\": 4.3, \"battery\": 5600, \"storage\": 256, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(95, 1, 15, 'Смартфон Magic 6 Pro', '74990.00', NULL, NULL, 1, NULL, '{\"os\": \"MagicOS 8.0\", \"ram\": 12, \"brand\": \"Honor\", \"color\": \"синий\", \"model\": \"Honor Magic 6 Pro\", \"rating\": 4.3, \"battery\": 5600, \"storage\": 512, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(96, 1, 15, 'Смартфон Magic 6 Pro', '84990.00', NULL, NULL, 1, NULL, '{\"os\": \"MagicOS 8.0\", \"ram\": 12, \"brand\": \"Honor\", \"color\": \"фиолетовый\", \"model\": \"Honor Magic 6 Pro\", \"rating\": 4.3, \"battery\": 5600, \"storage\": 1024, \"processor\": \"Snapdragon 8 Gen 3\", \"release_year\": 2025}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(97, 1, 15, 'Смартфон Phone 2', '54990.00', NULL, NULL, 1, NULL, '{\"os\": \"Nothing OS 2.5\", \"ram\": 12, \"brand\": \"Nothing\", \"color\": \"белый\", \"model\": \"Nothing Phone 2\", \"rating\": 4.2, \"battery\": 4700, \"storage\": 256, \"processor\": \"Snapdragon 8+ Gen 1\", \"release_year\": 2024}', '2026-03-02 19:09:39', '2026-03-02 19:09:39'),
(98, 1, 15, 'Смартфон Phone 2', '54990.00', NULL, NULL, 1, NULL, '{\"os\": \"Nothing OS 2.5\", \"ram\": 12, \"brand\": \"Nothing\", \"color\": \"черный\", \"model\": \"Nothing Phone 2\", \"rating\": 4.2, \"battery\": 4700, \"storage\": 512, \"processor\": \"Snapdragon 8+ Gen 1\", \"release_year\": 2024}', '2026-03-02 19:09:39', '2026-03-02 19:09:39');

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('ccJS0ia4TqbDNiZlDCcAldYp2Mv7pgh2tMSlhtC8', 1, '109.254.254.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoibER6M0RqVkdpelFkb0dob0FqdmxTcmh6N2NNYThiRElnM21CVFIyayI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjgxOiJodHRwczovL2VjLWRuLnJ1Ojk4L2FkbWluL3Jlc291cmNlL3N1YnNjcmlwdGlvbi1yZXNvdXJjZS9zdWJzY3JpcHRpb24tZm9ybS1wYWdlLzEiO3M6NToicm91dGUiO3M6MjM6Im1vb25zaGluZS5yZXNvdXJjZS5wYWdlIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1NjoibG9naW5fbW9vbnNoaW5lXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjIzOiJwYXNzd29yZF9oYXNoX21vb25zaGluZSI7czo2NDoiOWIxZjYwM2Y2MzA4NDc5OTg3OTBmNmYzYTUwNmZjOWFjMDUwMzFjMWEyMmZiMjJlZTRjNDE4ZWY0ZWExM2E1YiI7fQ==', 1771668529),
('H4whS41NF6QXs1uIQrEGIZNNXLPsOwsVXi0JvHQ9', NULL, '216.180.246.103', 'Mozilla/5.0 (compatible; GenomeCrawlerd/1.0; +https://www.nokia.com/genomecrawler)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNDc4QWpqcHB6cU1TWk05SHBNU0RyYWVDNVlXQWFiRXd4Q2tHQncyUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vMTc2LjExMy44Mi4xNTE6OTgvYWRtaW4vbG9naW4uYXNwIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1773272318),
('vVvWaadS7t1BkyKzdCN6ZJWhVfTFKq9Uutd9feo6', NULL, '216.180.246.103', 'Mozilla/5.0 (compatible; GenomeCrawlerd/1.0; +https://www.nokia.com/genomecrawler)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibUZTSHJUS1VYWHpsSE0yQUlkN2ZJaHJiZlh1RVhYSWxSQ2w1V1VRZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDI6Imh0dHBzOi8vMTc2LjExMy44Mi4xNTE6OTgvYWRtaW4vaW5kZXguaHRtbCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1773272313),
('ZXOAAtDu7QW5jWhEwlGIEKtUygS7pDioeIuZrpnH', 1, '109.254.254.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiOUZpSzh6SUJmRWRGTVlKTkVLUTBQM3VSczRGS040WUhpZXhlazkzNiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjgxOiJodHRwczovL2VjLWRuLnJ1Ojk4L2FkbWluL3Jlc291cmNlL3N1YnNjcmlwdGlvbi1yZXNvdXJjZS9zdWJzY3JpcHRpb24tZm9ybS1wYWdlLzEiO3M6NToicm91dGUiO3M6MjM6Im1vb25zaGluZS5yZXNvdXJjZS5wYWdlIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1NjoibG9naW5fbW9vbnNoaW5lXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjIzOiJwYXNzd29yZF9oYXNoX21vb25zaGluZSI7czo2NDoiOWIxZjYwM2Y2MzA4NDc5OTg3OTBmNmYzYTUwNmZjOWFjMDUwMzFjMWEyMmZiMjJlZTRjNDE4ZWY0ZWExM2E1YiI7fQ==', 1771686802);

-- --------------------------------------------------------

--
-- Структура таблицы `shops`
--

CREATE TABLE `shops` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_token` text COLLATE utf8mb4_unicode_ci,
  `notification_chat_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Самовывоз',
  `delivery_price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `shops`
--

INSERT INTO `shops` (`id`, `user_id`, `name`, `bot_token`, `notification_chat_id`, `delivery_name`, `delivery_price`, `created_at`, `updated_at`) VALUES
(1, 11, 'Тестовый магазин', 'eyJpdiI6IjZoTXBvYUIyME53bS93K0ZvNk9hNEE9PSIsInZhbHVlIjoiSnpBejdLUmFZd1NrVzZxOW80UzUzYTU4UkM4aEl1MGIrQk5GWU5tMDV1N2RqSXNGZTRibUlDMjBXZ1ZMWENaZiIsIm1hYyI6ImU4YjhkMDIzYzc3NjIwNjM2M2Y0NWY0MjY1MzRlZmNiMGFiYmQzNGE2MzEzMTRhNmJkZDVhMmIxMDQ4ZTU0ZTEiLCJ0YWciOiIifQ==', '954773719', 'Самовывоз', '0.00', '2026-02-22 10:08:38', '2026-02-22 10:08:38');

-- --------------------------------------------------------

--
-- Структура таблицы `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `plan` enum('starter','business','premium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'starter',
  `status` enum('active','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expired',
  `expires_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `price` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yookassa_payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan`, `status`, `expires_at`, `auto_renew`, `price`, `payment_method`, `yookassa_payment_id`, `created_at`, `updated_at`) VALUES
(1, 10, 'starter', 'active', '2026-03-21 09:42:54', 1, '990.00', NULL, NULL, '2026-02-21 09:42:54', '2026-02-21 10:02:04'),
(2, 11, 'starter', 'active', '2026-03-21 14:33:25', 1, '990.00', NULL, NULL, '2026-02-21 14:33:25', '2026-02-21 14:33:25');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `telegram_id` bigint DEFAULT NULL,
  `telegram_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram_linked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `telegram_id`, `telegram_username`, `telegram_linked_at`) VALUES
(10, 'Test User', 'test@example.com', NULL, '$2y$12$UI5DF8FHdyiuDzSm6jfZkulxx1a5hSNsdtR7gK1yKrSO9rxfmJIOy', NULL, '2026-02-01 16:23:38', '2026-02-01 16:23:38', NULL, NULL, NULL),
(11, 'test2', 'test2@gmail.com', NULL, '$2y$12$it6GgT1kMdKxrP2tpNc0dO0t60n7T4FqHMKYIWVXAwFX2gD.Au2dG', NULL, '2026-02-01 17:02:26', '2026-02-02 16:05:40', 954773719, 'vveb_front', '2026-02-02 16:05:40');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Индексы таблицы `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_shop_id_name_unique` (`shop_id`,`name`);

--
-- Индексы таблицы `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Индексы таблицы `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Индексы таблицы `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `moonshine_users`
--
ALTER TABLE `moonshine_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `moonshine_users_email_unique` (`email`),
  ADD KEY `moonshine_users_moonshine_user_role_id_foreign` (`moonshine_user_role_id`);

--
-- Индексы таблицы `moonshine_user_roles`
--
ALTER TABLE `moonshine_user_roles`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orders_shop_id_index` (`shop_id`),
  ADD KEY `orders_status_index` (`status`),
  ADD KEY `orders_yookassa_payment_id_index` (`yookassa_payment_id`);

--
-- Индексы таблицы `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Индексы таблицы `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_shop_id_index` (`shop_id`),
  ADD KEY `products_category_index` (`category`),
  ADD KEY `products_category_id_foreign` (`category_id`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Индексы таблицы `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shops_user_id_index` (`user_id`);

--
-- Индексы таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_user_id_status_index` (`user_id`,`status`),
  ADD KEY `subscriptions_expires_at_index` (`expires_at`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_telegram_id_unique` (`telegram_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `moonshine_users`
--
ALTER TABLE `moonshine_users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `moonshine_user_roles`
--
ALTER TABLE `moonshine_user_roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT для таблицы `shops`
--
ALTER TABLE `shops`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `moonshine_users`
--
ALTER TABLE `moonshine_users`
  ADD CONSTRAINT `moonshine_users_moonshine_user_role_id_foreign` FOREIGN KEY (`moonshine_user_role_id`) REFERENCES `moonshine_user_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
