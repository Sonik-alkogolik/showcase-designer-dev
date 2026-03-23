# Chat Handoff: showcase-designer

Обновлено: 2026-03-23

## Единый протокол фокуса

- `CHAT_HANDOFF.md` = единственный источник правды по статусу "где остановились".
- `PLAN_TABLE.md` = общий бэклог/карта задач.
- `ROADMAP_SERVICE.md` = этапы развития по сервису.
- `PROJECT_FOCUS.md` = фокус текущей сессии.

## Точка остановки (зафиксировано на завтра)

1. Фокус команды: core-flow сервиса, без ухода в маркетинговые лендинги.
2. Последний завершенный блок: обновлён гостевой экран и hero-demo (анимационный слайдер), изменения отправлены в `origin/main`.
3. Следующий рабочий блок:
   - пройти и стабилизировать core-flow: `register -> login -> create-shop -> products -> webapp`;
   - собрать список узких мест и исправить критичные в этом же проходе.
4. Definition of done на ближайшую сессию:
   - базовый путь пользователя воспроизводим без блокеров;
   - все найденные проблемы и решения зафиксированы в этом файле.

## Start Tomorrow (быстрый вход за 5 минут)

1. Прочитать только этот файл (`CHAT_HANDOFF.md`) и `PROJECT_FOCUS.md`.
2. Поднять dev-окружение:
   - `.\scripts\dev-shortcuts.ps1 db-up`
   - `php -S 127.0.0.1:8000 tools/dev-router.php`
3. Проверить UI:
   - гостевой экран `/`
   - auth `/login`
   - магазины `/shops`
   - товары `/shops/{id}/products`
4. Пройти core-flow и фиксировать найденные проблемы сразу в раздел "Статус на сейчас" этого файла.
5. В конце сессии обновить:
   - `CHAT_HANDOFF.md` (что сделано / что блокирует / что делаем следующим)
   - `PROJECT_FOCUS.md` (одно текущее действие на старт)

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
