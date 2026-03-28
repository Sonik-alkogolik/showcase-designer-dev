# Chat Handoff: showcase-designer

Обновлено: 2026-03-28

## Единый протокол фокуса

- `CHAT_HANDOFF.md` = единственный источник правды по статусу "где остановились".
- `PLAN_TABLE.md` = общий бэклог/карта задач.
- `ROADMAP_SERVICE.md` = этапы развития по сервису.
- `PROJECT_FOCUS.md` = фокус текущей сессии.

## Точка остановки

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

8. Core-flow smoke (2026-03-24):
   - Подтверждена доступность базовых экранов и WebApp:
     - `GET /` -> `200`
     - `GET /login` -> `200`
     - `GET /app?shop=2` -> `200`
   - Подтвержден рабочий backend-путь для магазина/товаров:
     - `tests/Feature/Shop/ShopCreationTest.php` -> PASS
     - `tests/Feature/Shop/ProductCategoryResolutionTest.php` -> PASS (3 tests)
     - `GET /api/shops/2/products` возвращает данные (не пустой ответ).
   - Выявлен инфраструктурный блокер dev-среды для браузерных E2E: Playwright падает с `WinError 5 (Access is denied)` при создании subprocess.

9. Checkout + draft order API (2026-03-24):
   - Обновлен `POST /api/orders`:
     - по умолчанию создаёт черновик заказа (`pending`) без обязательной интеграции ЮKassa;
     - суммы и состав заказа считаются на сервере по реальным товарам магазина (без доверия к клиентским `price/name`);
     - добавлена валидация: товары должны принадлежать текущему магазину и быть в наличии;
     - оплата через ЮKassa остаётся опциональной (`create_payment=true` + настроенные ключи).
   - Обновлен `client/src/views/telegram/WebAppView.vue`:
     - checkout-форма отправляет создание черновика заказа (`create_payment: false`);
     - добавлен блок способа доставки (из `shop.delivery_name` и `shop.delivery_price`);
     - улучшены тексты статусов/ошибок для пользователя.
   - Добавлены feature-тесты:
     - `tests/Feature/Order/OrderDraftApiTest.php` (4 passing).

10. Payment flow в WebApp (2026-03-24):
   - В `client/src/views/telegram/WebAppView.vue` добавлен чекбокс онлайн-оплаты (`payOnline`).
   - Если API возвращает `confirmation_url`, WebApp открывает оплату через `openInvoice/openLink`.
   - Добавлен polling статуса оплаты через `GET /api/orders/payment/{paymentId}`.
   - Если ЮKassa не настроена (`create_payment=true`), UI автоматически сохраняет заказ как черновик и показывает понятное сообщение.
   - Прогон с тестовыми данными:
     - черновик создается успешно (`DRAFT_SUCCESS=True`);
     - ветка оплаты без ключей возвращает корректный `HTTP 422` с ошибкой `create_payment`.

11. Telegram real-user smoke (2026-03-24):
   - Найден и устранён конфликт dev-сервера: порт `8000` периодически перехватывал чужой проект `pars/backend`, из-за чего `/app` отдавал `404` и ломал WebApp-проверку.
   - Для текущего проекта подтверждено:
     - `http://127.0.0.1:8000/app?shop=2` -> `200` (после очистки конфликта порта).
   - Проблема с Telegram тестом покупки остаётся инфраструктурной:
     - нестабильность публичных туннелей (`localtunnel` и временные домены `localhost.run`) приводит к `503` / `no tunnel here`.
   - В Telegram отправлены свежие кнопки `Open Shop`, но нужен стабильный tunnel URL на время ручного прохода покупки.

12. Checkout/payment smoke automation (2026-03-28):
   - Доработан `tools/e2e_checkout_to_payment.py`:
     - добавлены preflight-проверки `local /app?shop={id}` и опционально `public /app?shop={id}` (`--public-url --check-public-app`);
     - добавлен polling статуса оплаты через `GET /api/orders/payment/{paymentId}` c настраиваемыми интервалами;
     - добавлен fallback-режим `--allow-draft-fallback` (если `create_payment` недоступен — сохранить заказ как draft);
     - добавлена диагностика `LIKELY_WRONG_BACKEND_ON_BASE_URL`, если на `base-url` отвечает чужой Laravel-проект.
   - Добавлен shortcut:
     - `.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -ShopId "2"`
     - `.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -PublicUrl "https://xxxx.loca.lt" -ShopId "2"`
   - Обновлены `QUICK_COMMANDS.md` и `DEV_ENV_RUNBOOK.md` под новый smoke-шаг.
   - Проверки:
     - `tests/Feature/Order/OrderDraftApiTest.php` -> PASS (4 tests, 26 assertions).
     - `python tools/e2e_checkout_to_payment.py --shop-id 2 --allow-draft-fallback --no-open-browser` -> FAIL c детекцией `LIKELY_WRONG_BACKEND_ON_BASE_URL=true` (на `127.0.0.1:8000` слушает чужой `php`, трасса указывает на `pars_back_up/backend_app`).

13. Order notifications + shop settings flow (2026-03-28):
   - Backend:
     - Добавлен сервис `app/Services/OrderNotificationService.php`:
       - уведомление в Telegram о создании заказа (`order.created`) и оплате (`order.paid`);
       - fallback по `notification_username`: поиск `chat_id` через `getUpdates`;
       - отправка внешнего webhook на `shop.webhook_url` с payload заказа.
     - Обновлён `app/Http/Controllers/OrderController.php`:
       - после создания заказа вызывается отправка уведомлений/внешнего webhook;
       - webhook ЮKassa теперь обрабатывает `payment.succeeded` и `payment.canceled`;
       - добавлена проверка консистентности статуса webhook vs provider API (`getPaymentInfo`) + idempotent-уведомление о `paid`.
     - Добавлены поля магазина:
       - миграция `database/migrations/2026_03_28_131500_add_notification_fields_to_shops_table.php`;
       - `shops.notification_username`;
       - `shops.webhook_url`;
       - валидация/сохранение в `ShopController`.
   - Frontend:
     - В `CreateShopView.vue` добавлены поля:
       - `notification_username`,
       - `webhook_url`.
     - Добавлена страница настроек магазина:
       - `client/src/views/ShopSettingsView.vue`,
       - маршрут `/shops/:shopId/settings`,
       - кнопка редактирования в `ShopsView.vue` теперь открывает настройки.
   - Тесты:
     - `tests/Feature/Order/OrderNotificationsTest.php` -> PASS (2 tests),
     - `tests/Feature/Order/OrderDraftApiTest.php` -> PASS (4 tests),
     - `tests/Feature/Shop/ShopCreationTest.php` -> PASS (1 test),
     - итого: `7 passed`, `37 assertions`.
   - Инфраструктурный результат smoke:
     - подтвержден локальный smoke checkout/payment:
       - `python tools/e2e_checkout_to_payment.py --shop-id 1 --allow-draft-fallback --no-open-browser` -> `PAYMENT_E2E_OK: true`;
       - режим: `draft_or_payment_unavailable` (без ключей ЮKassa), `LOCAL_APP_STATUS: 200`;
     - выявлено расхождение окружения БД:
       - MySQL поднят на `127.127.126.50:3306`, а `.env` ожидает `127.127.126.26`.
       - База `showcase_designer` восстановлена из `showcase_designer.sql` на `127.127.126.50`.
     - public-smoke не выполнен:
       - tunnel URL не получен в текущем sandbox-окружении (`PUBLIC_TUNNEL_URL_NOT_FOUND`).

### Что не сделано

1. В dev-тестировании Telegram WebApp туннели периодически падают (`503 Tunnel Unavailable` / `no tunnel here`), поэтому URL приходится переподнимать и перепинить.
2. Не завершены следующие блоки из `PLAN_TABLE.md`:
   - интеграция ЮKassa,
   - реальный end-to-end прогон ЮKassa с валидными ключами,
   - финальные блоки тестирования/запуска MVP.

## Ключевой блокер (актуализировано)

1. Блокер проверки UI в текущей среде: Playwright E2E (`tools/e2e_*.py`) падают с `PermissionError [WinError 5]` при запуске браузерного subprocess.
2. Основной блокер ручной Telegram-покупки: нестабильность публичных туннелей в dev (`localtunnel` / `localhost.run`), из-за чего WebApp становится недоступен.
3. Технический риск учтён: на машине может подниматься сторонний сервер на `:8000` (`pars/backend`), который перехватывает `/app`.
4. На текущий момент это подтверждено автоматически smoke-скриптом: при конфликте он помечает `LIKELY_WRONG_BACKEND_ON_BASE_URL=true` в `tools/e2e_checkout_to_payment_report.json`.
5. Новый инфраструктурный риск: рабочий MySQL в текущей среде доступен на `127.127.126.50`, а базовые dev-скрипты заточены под `127.127.126.26`.
6. Public tunnel не поднимается из текущего sandbox-окружения (не удаётся получить URL), поэтому шаг `--check-public-app` не закрыт.

## Следующий шаг

1. Синхронизировать dev-конфиг БД (привести `DB_HOST` к рабочему значению `127.127.126.50` или поднять MySQL на `127.127.126.26`).
2. Зафиксировать smoke на актуальном магазине:
   - `.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -ShopId "1"` (локально).
3. Поднять стабильный public tunnel вне sandbox и выполнить:
   - `.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -PublicUrl "https://<tunnel>" -ShopId "1"`.
4. Перепривязать бота и отправить свежую кнопку `Open Shop`, после чего пройти реальную покупку из Telegram вручную.
5. После успешного Telegram-smoke закрыть production-блок:
   - реальный платёж ЮKassa с валидными ключами;
   - webhook `payment.succeeded`/`payment.canceled` на живом событии;
   - проверка доставки уведомлений и внешнего webhook в боевом сценарии.

## Быстрые опорные файлы

- `client/src/App.vue`
- `client/src/views/telegram/WebAppView.vue`
- `app/Http/Controllers/ProductController.php`
- `app/Imports/AdvancedProductsImport.php`
- `tests/Feature/Shop/ProductCategoryResolutionTest.php`
