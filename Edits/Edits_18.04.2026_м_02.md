# Этап 1 нормализации и масштабирования (запуск)

Обновлено: 2026-04-18
Файл правок: `Edits_18.04.2026_м_02.md`

## Что реализовано

1. Добавлены индексы для multi-tenant нагрузки:
- `shops(user_id, created_at)`
- `categories(shop_id, is_active, sort_order)`
- `products(shop_id, category_id)`
- `products(shop_id, in_stock, created_at)`
- `products(shop_id, show_in_slider)`
- `orders(shop_id, status, created_at)`
- `subscriptions(user_id, status, expires_at)`

Миграция:
`database/migrations/2026_04_18_120000_add_scaling_indexes_for_multi_tenant_tables.php`

2. Добавлена команда аудита архитектуры данных:
- проверка осиротевших связей;
- проверка консистентности category linkage;
- проверка объема legacy-использования `products.category`.

Команда:
`php artisan app:audit-data-architecture`

Файл:
`app/Console/Commands/AuditDataArchitecture.php`

3. Добавлена команда backfill для привязки `products.category_id`:
- сопоставляет товары по legacy-полю `products.category`;
- при необходимости создает недостающие категории в пределах магазина;
- поддерживает безопасный режим `--dry-run`.

Команда:
`php artisan app:backfill-product-category-links --dry-run`
`php artisan app:backfill-product-category-links`

Файл:
`app/Console/Commands/BackfillProductCategoryLinks.php`

4. Зафиксирован архитектурный план масштабирования:
- 1 БД (усиленная) -> 2 БД (`core_db` + `catalog_db`) -> шардирование `catalog`.

Файл:
`md/SCALING_ARCHITECTURE_PLAN.md`

## Следующий шаг

1. Выполнить миграции.
2. Запустить `audit-data-architecture`.
3. Запустить backfill в `--dry-run`, затем в боевом режиме.
4. По результатам подготовить миграцию с более строгими `UNIQUE`-ограничениями.
