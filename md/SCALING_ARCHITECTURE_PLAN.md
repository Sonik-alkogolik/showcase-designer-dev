# Scaling Architecture Plan (core + catalog + sharding)

Обновлено: 2026-04-18

## Цель

Подготовить проект к росту пользователей и каталога без резкого усложнения продакшена.

## Этап 0 (текущий, обязательный)

Оставляем одну БД, но усиливаем multi-tenant дисциплину:

1. Все ключевые запросы фильтруются по `shop_id`/`user_id`.
2. Добавляем составные индексы под реальные сценарии чтения.
3. Убираем “долги” по legacy-данным:
- `products.category_id` должен быть основным источником категории;
- `products.category` оставляем временно для совместимости.
4. Тяжёлые операции переводим в очереди (`jobs`): импорт, очистка, бэкфилл.

### Что уже сделано в рамках этапа 0

1. Добавлена миграция индексов:
- `shops(user_id, created_at)`
- `categories(shop_id, is_active, sort_order)`
- `products(shop_id, category_id)`
- `products(shop_id, in_stock, created_at)`
- `products(shop_id, show_in_slider)`
- `orders(shop_id, status, created_at)`
- `subscriptions(user_id, status, expires_at)`

2. Добавлена команда аудита:
- `php artisan app:audit-data-architecture`

3. Добавлена команда backfill для legacy category:
- `php artisan app:backfill-product-category-links --dry-run`
- `php artisan app:backfill-product-category-links`

4. Добавлен async-импорт через queue:
- таблица запусков импорта `import_runs`;
- job `ProcessImportRunJob` (очередь `imports`);
- API:
  - `POST /api/shops/{shop}/import/async`
  - `GET /api/shops/{shop}/import/status/{run}`
  - `GET /api/shops/{shop}/import/history`

## Этап 1 (рост до 100-500 магазинов)

Усиливаем целостность схемы без перехода на несколько БД:

1. После backfill добавить более строгие ограничения:
- `UNIQUE (shop_id, slug)` уже есть в categories;
- добавить `UNIQUE (shop_id, name)` для categories после дедупликации.
2. Завести асинхронный импорт (job + статус импорта).
3. Включить Redis для queue/cache (если ещё не включён).
4. Ввести регулярный аудит данных через cron:
- `app:audit-data-architecture`.

## Этап 2 (рост до 500-3000 магазинов)

Переход к двум базам:

1. `core_db`:
- users, shops, subscriptions, orders, payments, auth.
2. `catalog_db`:
- categories, products, product-media, product-attributes.
3. Межбазовые join не используем в рантайме:
- собираем данные на уровне приложения/API.
4. Импорт и массовые апдейты работают только с `catalog_db`.

## Этап 3 (очень большой каталог)

Шардирование только `catalog_db`:

1. Шард-ключ: `shop_id`.
2. Маршрутизация по формуле `shop_id % N`.
3. Каждый магазин закреплён за одним shard.
4. Аналитика по всем шардам уходит в отдельный слой (ETL/DWH).

## Удаление аккаунта пользователя (self-service)

Рекомендуемая модель:

1. Пользователь инициирует удаление.
2. Аккаунт получает статус `pending_deletion`, доступ блокируется.
3. Фоновые jobs удаляют tenant-данные (shops/products/categories/orders/cache/files).
4. По завершению — удаление или анонимизация пользовательской записи.
5. Для безопасности — grace period (например 30 дней).

## Операционные команды

1. Аудит:
`php artisan app:audit-data-architecture`

2. Backfill category links:
`php artisan app:backfill-product-category-links --dry-run`
`php artisan app:backfill-product-category-links`
