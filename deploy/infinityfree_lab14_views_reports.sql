-- ЛР14 · MySQL 8+/MariaDB · импорт через phpMyAdmin на InfinityFree.
-- Создаёт только новые таблицы, старые статьи/пользователи не трогает.
-- Сначала сделайте резервную копию базы.
CREATE TABLE IF NOT EXISTS `article_views` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `article_id` bigint unsigned NOT NULL,
    `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `viewed_at` timestamp NOT NULL,
    PRIMARY KEY (`id`),
    KEY `article_views_viewed_at_index` (`viewed_at`),
    KEY `article_views_article_id_viewed_at_index` (`article_id`,`viewed_at`),
    CONSTRAINT `article_views_article_id_foreign` FOREIGN KEY (`article_id`)
        REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_report_deliveries` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `moderator_id` bigint unsigned NOT NULL,
    `report_date` date NOT NULL,
    `sent_at` timestamp NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `daily_report_deliveries_moderator_id_report_date_unique` (`moderator_id`,`report_date`),
    CONSTRAINT `daily_report_deliveries_moderator_id_foreign` FOREIGN KEY (`moderator_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Отмечаем обе миграции; команда migrate не должна создавать таблицы второй раз.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_19_000007_create_article_views_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
HAVING COUNT(CASE WHEN `migration` = '2026_11_19_000007_create_article_views_table' THEN 1 END) = 0;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_19_000008_create_daily_report_deliveries_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
HAVING COUNT(CASE WHEN `migration` = '2026_11_19_000008_create_daily_report_deliveries_table' THEN 1 END) = 0;
