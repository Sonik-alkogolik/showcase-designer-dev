# Этап 2: завершение блока архитектуры и очередей импорта

Обновлено: 2026-04-18
Файл правок: `Edits_18.04.2026_м_03.md`

## Что добавлено

1. Асинхронный импорт товаров через очередь:
- новая таблица запусков: `import_runs`;
- новая модель: `ImportRun`;
- новый job: `ProcessImportRunJob` (очередь `imports`);
- новые API:
  - `POST /api/shops/{shop}/import/async`
  - `GET /api/shops/{shop}/import/status/{run}`
  - `GET /api/shops/{shop}/import/history`

2. Жесткая нормализация категорий:
- миграция дедупликации категорий в рамках `(shop_id, name)`;
- перепривязка `products.category_id` на сохраненную категорию;
- добавление `UNIQUE (shop_id, name)` в `categories`.

3. Дополнен архитектурный документ:
- зафиксирован async-процесс импорта и API статусов.

## Измененные/добавленные файлы

1. `database/migrations/2026_04_18_123000_create_import_runs_table.php`
2. `database/migrations/2026_04_18_124000_dedupe_categories_and_add_unique_shop_name.php`
3. `app/Models/ImportRun.php`
4. `app/Jobs/ProcessImportRunJob.php`
5. `app/Http/Controllers/ImportController.php`
6. `routes/api.php`
7. `app/Models/Shop.php`
8. `app/Models/User.php`
9. `md/SCALING_ARCHITECTURE_PLAN.md`

## План проверки на сервере

1. Выполнить миграции.
2. Проверить наличие новых API импорта.
3. Запустить очереди и протестировать async-импорт.
4. Прогнать аудит и backfill команды.
