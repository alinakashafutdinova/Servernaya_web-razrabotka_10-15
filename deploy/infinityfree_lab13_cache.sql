-- ЛР №13: импорт в MySQL базы этого сайта через phpMyAdmin.
-- Не удаляет существующие данные, можно безопасно повторить.
CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(191) NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` VARCHAR(191) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Пометить миграцию как выполненную (предполагается существующая migrations).
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_19_000006_create_cache_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
HAVING COUNT(CASE WHEN `migration` = '2026_11_19_000006_create_cache_table' THEN 1 END) = 0;
