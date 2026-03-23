# Chat Handoff: showcase-designer

Обновлено: 2026-03-23

## Статус на сейчас

### Что сделано

0. DEV-запуск Telegram WebApp (обновление):
   - Добавлены команды в `scripts/dev-shortcuts.ps1`:
     - `db-up` (поднять MySQL + ping),
     - `serve` (Laravel на `127.0.0.1:8000`),
     - `tunnel-up` (поднять localtunnel и получить URL),
     - `telegram-pin-current-tunnel` (закрепить бота на URL из `tools/tmp-lt.out`).
   - Исправлен парсинг URL туннеля в `telegram-pin-current-tunnel` (ранее мог некорректно взять URL).
   - Добавлен runbook: `DEV_ENV_RUNBOOK.md`.
   - Обновлён `QUICK_COMMANDS.md` с новым рабочим циклом для Telegram.

1. Магазины:
   - Добавлена кнопка удаления магазина в UI (`client/src/views/ShopsView.vue`).
   - Реализовано удаление через `DELETE /api/shops/{id}` с `confirm` и моментальным обновлением списка.

2. Категории/товары (backend):
   - В `ProductController` сделан единый резолвер категории для `store/update`.
   - Исправлено связывание товара с категорией при создании по имени категории.
   - Добавлена проверка, что `category_id` принадлежит текущему магазину.
   - Добавлены feature-тесты:
     - `tests/Feature/Shop/ProductCategoryResolutionTest.php` (3 passing).

3. Категории/товары (frontend):
   - В `ProductsView.vue` исправлен вывод категории (теперь имя категории, не JSON-объект).
   - Исправлено заполнение формы редактирования товара, чтобы не подставлялся объект relation.

4. Импорт:
   - Проверен реальный импорт `test-products.csv` через API (`preview -> import`) для магазина `2`.
   - Исправлен баг `in_stock`: значение `0` теперь корректно парсится в `false`.
   - Коммит с фиксом:
     - `e403504 Fix import in_stock parsing for numeric zero and false-like values`
     - уже отправлен в `origin/main`.

5. Telegram WebApp:
   - Улучшен `WebAppView.vue`: корректная работа категорий (id/name), отображение категории как текста.
   - Убрана глобальная шапка (`Navbar`) для WebApp-сценариев в `App.vue`.
   - Добавлены адаптивные правки фильтров/селекта категорий для узких экранов.
   - Пересобран frontend и скопирован в `public`.

6. План:
   - В `PLAN_TABLE.md` переведены в `✅` пункты:
     - SPA `/app`
     - базовый экран WebApp
     - каталог товаров
     - клиентская корзина в WebApp (localStorage, add/remove/update qty, суммы)

7. Корзина WebApp (дополнение):
   - В `client/src/views/telegram/WebAppView.vue` корзина сделана изолированной по магазину:
     - ключ хранения: `webapp_cart_shop_{shopId}`.
   - Это устраняет смешивание товаров между разными магазинами при открытии разных витрин.

### Что не сделано

1. В dev используется `localtunnel`, который может периодически падать (`503 Tunnel Unavailable`), поэтому URL иногда нужно переподнимать и перепинить.
2. Не завершены следующие блоки из `PLAN_TABLE.md`:
   - корзина (localStorage),
   - оформление заказа,
   - модель/поток заказа,
   - интеграция ЮKassa,
   - уведомления в Telegram и webhook для внешних систем,
   - финальные блоки тестирования/запуска MVP.

## Ключевой блокер (актуализировано)

Основной риск в dev: нестабильность `localtunnel` (возможны `502/503` и отвалившийся tunnel-процесс).
При падении туннеля Telegram WebApp недоступен до перезапуска `tunnel-up` и повторного pin URL.

## Следующий шаг

1. Для локальной dev-проверки:
   - `.\scripts\dev-shortcuts.ps1 db-up`
   - `.\scripts\dev-shortcuts.ps1 serve`
   - `.\scripts\dev-shortcuts.ps1 tunnel-up`
   - `.\scripts\dev-shortcuts.ps1 telegram-pin-current-tunnel -ShopId "2"`
2. Для более стабильного dev-URL рассмотреть замену `localtunnel` на альтернативный туннель без частых отвалов.
3. Продолжить незакрытые MVP-блоки из `PLAN_TABLE.md`.

## Быстрые опорные файлы

- `client/src/App.vue`
- `client/src/views/telegram/WebAppView.vue`
- `app/Http/Controllers/ProductController.php`
- `app/Imports/AdvancedProductsImport.php`
- `tests/Feature/Shop/ProductCategoryResolutionTest.php`
