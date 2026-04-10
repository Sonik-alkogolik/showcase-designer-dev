# План проекта: showcase-designer-dev

> Процесс фиксации: "где остановились" ведем в `CHAT_HANDOFF.md` (single source of truth).  
> `PLAN_TABLE.md` — это стратегическая карта задач и статусов.

## Сводка статуса на 2026-03-28

### Сделано

- Базовая auth API (Laravel Breeze + Sanctum) и frontend авторизация.
- Telegram-link в профиле (привязка/отвязка вместо старой схемы с кодом).
- Базовые сущности подписок/магазинов/товаров и ключевые ограничения (подписка, лимиты магазинов, ownership).
- Импорт товаров (CSV/XLSX) с фиксом `in_stock=0` (коммит `e403504` в `origin/main`).
- WebApp SPA (`/app`), базовый экран и каталог товаров.
- Исправления категорий в backend/frontend для товаров + feature-тест `ProductCategoryResolutionTest` (3 passing).
- UI магазинов: добавлено удаление магазина из списка с подтверждением и мгновенным обновлением.
- Checkout/payment smoke вынесен в отдельный воспроизводимый сценарий:
  - `tools/e2e_checkout_to_payment.py` (preflight local/public app URL, payment status polling, draft fallback),
  - shortcut `.\scripts\dev-shortcuts.ps1 smoke-checkout-payment`.
  - подтверждён локальный прогон (`shop_id=1`, `PAYMENT_E2E_OK: true`, draft fallback без ключей ЮKassa).
- Уведомления по заказам и внешние webhook-и:
  - добавлен `OrderNotificationService` (Telegram + `shop.webhook_url`),
  - fallback по `notification_username` через `getUpdates`,
  - новые поля магазина: `notification_username`, `webhook_url`,
  - добавлен UI настроек магазина `/shops/:shopId/settings`.

### Не сделано

- Валидация bot token через `getMe` (статус в таблице: в разработке).
- Полный production-проход order flow (реальный платёж + подтверждение webhook на боевых событиях).
- Интеграция ЮKassa и webhook-и оплаты.
- Ручная валидация доставки уведомлений в Telegram и внешний webhook в реальном E2E.
- Финальные блоки тестирования, cron, hardening API, документация и прод-старт MVP.

### Ближайший блокер

- На `127.0.0.1:8000` периодически поднимается чужой backend (`php` из другого проекта), из-за чего smoke и Telegram WebApp проверяют не тот сервис.
- Публичные tunnel URL нестабильны (`503`, `no tunnel here`), поэтому нужен preflight перед каждым ручным прогоном.
- В текущей среде MySQL доступен на `127.127.126.50`, тогда как часть dev-скриптов ожидает `127.127.126.26`.

| Блок                         | Задача (промт)                                                                                                                               | Статус       |
|------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| Ядро пользователя и безопасности | Создай базовую аутентификацию в Laravel 12: регистрация, вход, выход. Используй laravel/breeze с API-режимом (Sanctum). Настрой маршруты /api/register, /api/login. Ответ должен возвращать токен. Сохрани токен во фронтенде (Vue 3) в localStorage. <br><br>**Реализация**: <ul><li>Установлен `laravel/breeze` с флагом `--api`</li><li>Опубликованы миграции Sanctum, таблица `personal_access_tokens` создана вручную (миграция пропущена)</li><li>В `routes/api.php` добавлены маршруты: `POST /register`, `POST /login`, `POST /logout`</li><li>Контроллеры: `RegisteredUserController@store`, `AuthenticatedSessionController@store/destroy`</li><li>Модель `User` использует `HasApiTokens`</li><li>Фронтенд: создан `useAuth.js` (composable), сохраняет токен в `localStorage`, устанавливает `Authorization: Bearer ...`</li><li>Компоненты: `LoginView.vue`, `RegisterView.vue`</li><li>Nginx настроен: `/` → `index.html` (SPA), `/api/*` → Laravel</li><li>Production-сборка Vue скопирована в `public/`</li></ul> | ✅ Выполнено |

| Ядро пользователя и безопасности | Реализуй обязательное подтверждение аккаунта через Telegram при регистрации. <br><br>**Фактическая реализация (обновлена):** вместо одноразового кода сделана привязка Telegram из профиля через токен `/start {token}`. <br>**Backend:** `telegram_verified_at` переименован в `telegram_linked_at`, удалён `telegram_verification_code`, добавлены методы `isTelegramLinked()/linkTelegram()/unlinkTelegram()`, API: `/api/profile`, `/api/profile/telegram/generate-token`, `/api/profile/telegram/unlink`, обновлён webhook привязки. <br>**Frontend:** `ProfileView.vue` с блоком «Привязка Telegram» (подключить/отвязать, отображение `@username`). | ✅ Выполнено |


| Ядро пользователя и безопасности | Создай страницу выбора тарифа (фронтенд: Vue 3 компонент). Добавь два чекбокса: «Согласен на автопродление…» (показывается только при выборе подписки с автопродлением), «Ознакомлен с офертой…». Ссылки: /offer и /privacy. | ✅ Выполнено |

| Ядро пользователя и безопасности | Создай миграцию и модель Subscription: поля user_id, plan (enum: 'starter', 'business', 'premium'), status (active/expired), expires_at, auto_renew (boolean). Свяжи с User.  | ✅ Выполнено |


| Ядро пользователя и безопасности | Реализуй возможность ручной активации подписки через админку MoonShine: создай ресурс SubscriptionResource, добавь форму для выбора пользователя, тарифа, срока и флага auto_renew.  | ✅ Выполнено |


| Ядро пользователя и безопасности | Настрой политики Laravel: пользователь может создавать магазины только если у него активная подписка. Создай middleware HasActiveSubscription.| ✅ Выполнено |


| Магазин и товары             | Создай модель Shop: поля user_id, name, bot_token (encrypted), notification_chat_id, delivery_name, delivery_price. Связь: один пользователь — много магазинов. | ✅ Выполнено |


| Магазин и товары             | Ограничь количество магазинов по тарифу: Starter = 1, Business = 5, Premium = 10. Добавь метод canCreateMoreShops() в модель User.  | ✅ Выполнено |
| Магазин и товары             | Создай API-маршрут /api/shops (POST): создаёт магазин, проверяет лимит и подписку. Верни ошибку, если лимит превышен. | ✅ Выполнено |
| Магазин и товары             | Создай модель Product: shop_id, name, price, description, category, in_stock (boolean). Добавь миграцию.  | ✅ Выполнено |
| Магазин и товары             | Подключи пакет maatwebsite/excel. Создай импортёр ProductImport, который парсит CSV/XLSX с колонками: название, цена, описание, категория, наличие (1/0).  | ✅ Выполнено |
| Магазин и товары             | Реализуй API-маршрут /api/shops/{shop}/import-products (POST, multipart/form-data). Принимает файл, запускает импорт, проверяет лимит товаров (пока без ограничения — заглушка). Валидируй расширение файла.  | ✅ Выполнено |
| Магазин и товары             | Добавь защиту: пользователь может управлять только своими магазинами. Создай policy ShopPolicy и middleware OwnsShop. | ✅ Выполнено |
| Telegram и Web App           | Реализуй валидацию токена бота: при сохранении bot_token в магазине — отправь GET-запрос к https://api.telegram.org/bot{token}/getMe. Если ошибка — верни 422. Храни токен в БД с шифрованием (используй Crypt::encryptString()). | ⏳ В разработке |

| Telegram и Web App           | Настрой Nginx: все запросы к /app должны отдавать index.html вашего Vue-приложения (SPA fallback). Убедись, что https://ec-dn.ru/app?shop=123 открывает Web App. | ✅ Выполнено |
| Telegram и Web App           | Создай базовый Vue-компонент WebApp.vue. При загрузке — читает ?shop=id из URL, делает запрос к /api/shops/{id}/public и показывает название магазина. Подключи window.Telegram.WebApp.ready(). | ✅ Выполнено |
| Telegram и Web App           | Реализуй каталог товаров: GET /api/shops/{shop}/products → возвращает список. Во Vue — отобрази карточки товаров (название, цена, кнопка «В корзину»). | ✅ Выполнено |
| Telegram и Web App           | Создай клиентскую корзину (в localStorage): добавление, удаление, изменение количества. Не отправляй на сервер — пока только UI. <br><br>**Реализация:** `client/src/views/telegram/WebAppView.vue` — добавлены операции корзины (add/remove/update qty), хранение в `localStorage`, подсчет сумм, экран корзины и переход к checkout. Корзина изолирована по `shopId` (ключ `webapp_cart_shop_{shopId}`), чтобы товары разных магазинов не смешивались. | ✅ Выполнено |
| Telegram и Web App           | Реализуй страницу оформления заказа во Vue: поля ФИО, телефон, выбор способа доставки (берётся из shop.delivery_name и shop.delivery_price). Кнопка «Оплатить». <br><br>**Реализация:** в `client/src/views/telegram/WebAppView.vue` добавлена checkout-форма с полями ФИО/телефон, блоком доставки из `shop.delivery_*`, отправкой на `/api/orders` и экраном успеха после создания заказа. | ✅ Выполнено |
| Telegram и Web App           | Создай модель Order: shop_id, customer_name, phone, total, delivery_name, delivery_price, status (pending/paid/cancelled), yookassa_payment_id. | ✅ Выполнено |
| Платежи и уведомления        | Подключи SDK ЮKassa (yoomoney/yookassa-sdk-php). Создай конфиг: YOOKASSA_SHOP_ID, YOOKASSA_SECRET_KEY в .env. | ⏳ В разработке |
| Платежи и уведомления        | Реализуй API-маршрут /api/orders (POST): создаёт черновик заказа, затем создаёт платёж в ЮKassa (метод оплаты — bank_card). Верни confirmation_url клиенту. <br><br>**Фактическая реализация на сейчас:** черновик заказа создаётся и валидируется (состав корзины/цены считаются на сервере), платёжная часть работает в опциональном режиме (`create_payment=true`) и требует настроенные ключи ЮKassa. | ⏳ В разработке |
| Платежи и уведомления        | Во Vue: после получения confirmation_url — вызови Telegram.WebApp.openInvoice(url) (или openLink, если инвойс не поддерживается). После возврата — обнови статус заказа. <br><br>**Фактическая реализация на сейчас:** во `WebAppView.vue` добавлен запуск `openInvoice/openLink`, polling статуса через `/api/orders/payment/{paymentId}` и fallback в черновик при недоступной ЮKassa. Для полного закрытия пункта нужен реальный прогон с валидными ключами ЮKassa. | ⏳ В разработке |
| Платежи и уведомления        | Настрой маршрут /webhooks/yookassa (POST). Подтверди подпись, обнови статус заказа на paid, если событие payment.succeeded. | ⏳ В разработке |
| Платежи и уведомления        | Реализуй отправку уведомления владельцу магазина: если в настройках магазина указан notification_chat_id — отправь сообщение через Telegram Bot API (sendMessage). Текст: «Новый заказ №{id} от {ФИО}, сумма: {total} ₽». | ⏳ В разработке |
| Платежи и уведомления        | Добавь альтернативу: если указан notification_username (например, @dimaivanov) — отправь личное сообщение. Используй getUpdates → message.from.id для получения ID (или предварительно сохраняй его при первом взаимодействии). | ⏳ В разработке |
| Платежи и уведомления        | Реализуй вебхук для внешних систем: при создании заказа — отправь POST-запрос на shop.webhook_url (если задан) с JSON тела заказа. Оберни в try/catch, логируй ошибки. | ⏳ В разработке |
| Тестирование и запуск        | Создай форму настроек магазина (Vue + API): редактирование названия, токена бота, способа доставки, webhook URL, чатов уведомлений. | ⏳ В разработке |
| Тестирование и запуск        | Протестируй сквозной сценарий: 1. Регистрация → подтверждение email, 2. Выбор тарифа → создание магазина, 3. Импорт товаров, 4. Оформление заказа → оплата → уведомление. Исправь найденные баги. | ⏳ В разработке |
| Тестирование и запуск        | Настрой cron-задачу: ежедневно проверяй subscriptions.expires_at < now() → деактивируй. Отправляй email за 3 дня до окончания. | ⏳ В разработке |
| Тестирование и запуск        | Добавь защиту от CSRF и rate-limiting на все API-маршруты (особенно /login, /register, /webhooks). | ⏳ В разработке |
| Тестирование и запуск        | Настрой логирование ошибок (Sentry или просто Log::error). Особенно — для вебхуков ЮKassa и Telegram. | ⏳ В разработке |
| Тестирование и запуск        | Создай простую документацию для пользователя: Как создать бота в @BotFather, Как получить Shop ID и ключ в ЮKassa, Как загрузить CSV. | ⏳ В разработке |
| Тестирование и запуск        | Запусти MVP на продакшене (ec-dn.ru). Проверь SSL, CSP, безопасность .env. Открой доступ для тестового пользователя. | ⏳ В разработке |

> 💡 **Примечание**: В таблице есть выполненные пункты не только из «Дня 1». Актуальный срез прогресса всегда смотри в блоке `Сводка статуса на 2026-03-22` выше.
