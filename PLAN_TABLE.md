# План проекта: showcase-designer
| Блок                         | Задача (промт)                                                                                                                               | Статус       |
|------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| Ядро пользователя и безопасности | Создай базовую аутентификацию в Laravel 12: регистрация, вход, выход. Используй laravel/breeze с API-режимом (Sanctum). Настрой маршруты /api/register, /api/login. Ответ должен возвращать токен. Сохрани токен во фронтенде (Vue 3) в localStorage. <br><br>**Реализация**: <ul><li>Установлен `laravel/breeze` с флагом `--api`</li><li>Опубликованы миграции Sanctum, таблица `personal_access_tokens` создана вручную (миграция пропущена)</li><li>В `routes/api.php` добавлены маршруты: `POST /register`, `POST /login`, `POST /logout`</li><li>Контроллеры: `RegisteredUserController@store`, `AuthenticatedSessionController@store/destroy`</li><li>Модель `User` использует `HasApiTokens`</li><li>Фронтенд: создан `useAuth.js` (composable), сохраняет токен в `localStorage`, устанавливает `Authorization: Bearer ...`</li><li>Компоненты: `LoginView.vue`, `RegisterView.vue`</li><li>Nginx настроен: `/` → `index.html` (SPA), `/api/*` → Laravel</li><li>Production-сборка Vue скопирована в `public/`</li></ul> | ✅ Выполнено |

Ядро пользователя и безопасности | Реализуй обязательное подтверждение аккаунта через Telegram при регистрации. При регистрации генерируется одноразовый 6-значный код (telegram_verification_code), который отправляется пользователю через бота. Заблокируй доступ к защищённым маршрутам до установки telegram_verified_at. Отключи email-верификацию.
Реализация:
База данных:
Добавлены поля в таблицу users:
telegram_id (BIGINT, уникальный) — для привязки к аккаунту
telegram_username (VARCHAR) — ник в Telegram
telegram_verification_code (VARCHAR(6)) — одноразовый код
telegram_verified_at (TIMESTAMP) — дата верификации
Middleware:
Создано и зарегистрировано middleware EnsureTelegramVerified для проверки верификации
Все защищённые маршруты используют middleware ensure.telegram.verified
Модель User:
Добавлены методы isTelegramVerified() и markTelegramAsVerified()
Поля добавлены в $fillable и $hidden (код верификации скрыт)
Контроллер регистрации:
RegisteredUserController@store генерирует 6-значный цифровой код
Код НЕ возвращается во фронтенд (только логируется для тестов)
Отправка кода через бота реализуется отдельно (шаг 4)
API-маршруты:
/api/register — регистрация, возвращает токен и статус верификации
/api/telegram/verify — принимает код от бота, верифицирует пользователя
/api/user — возвращает данные пользователя (требует авторизации)
/api/test-verification — тестовый маршрут для проверки доступа после верификации
Email-верификация:
Отключена (нет трейта MustVerifyEmail)

безопасности
Создали раздел "Личный кабинет" (профиль) с возможностью привязки и отвязки аккаунта Telegram.

Бэкенд:
- Миграция: переименовано telegram_verified_at → telegram_linked_at, удалено telegram_verification_code
- Модель User: методы isTelegramLinked(), linkTelegram($telegramId, $username), unlinkTelegram()
- Контроллер ProfileController: методы show(), generateTelegramLinkToken(), unlinkTelegram()
- Обновлён RegisteredUserController: убрана генерация кода при регистрации, возвращается telegram_linked: false
- Обновлён WebhookController: обработка /start {token} для привязки через токен из кэша
- Маршруты API: /api/profile (GET), /api/profile/telegram/generate-token (POST), /api/profile/telegram/unlink (DELETE)
- Middleware EnsureTelegramVerified проверяет isTelegramLinked()

Фронтенд (Vue 3):
- Компонент ProfileView.vue с маршрутом /profile
- Показывает данные пользователя (имя, email)
- Раздел "Привязка Telegram": кнопка "Подключить Telegram" → генерирует токен → открывает бота с командой /start {token}
- После привязки: показывает @username и кнопку "Отвязать"
- Использует useAuth.js для запросов с токеном авторизации


| Ядро пользователя и безопасности | Создай страницу выбора тарифа (фронтенд: Vue 3 компонент). Добавь два чекбокса: «Согласен на автопродление…» (показывается только при выборе подписки с автопродлением), «Ознакомлен с офертой…». Ссылки: /offer и /privacy. | ⏳ В разработке |
| Ядро пользователя и безопасности | Создай миграцию и модель Subscription: поля user_id, plan (enum: 'starter', 'business', 'premium'), status (active/expired), expires_at, auto_renew (boolean). Свяжи с User. | ⏳ В разработке |
| Ядро пользователя и безопасности | Реализуй возможность ручной активации подписки через админку MoonShine: создай ресурс SubscriptionResource, добавь форму для выбора пользователя, тарифа, срока и флага auto_renew. | ⏳ В разработке |
| Ядро пользователя и безопасности | Настрой политики Laravel: пользователь может создавать магазины только если у него активная подписка. Создай middleware HasActiveSubscription. | ⏳ В разработке |
| Магазин и товары             | Создай модель Shop: поля user_id, name, bot_token (encrypted), notification_chat_id, delivery_name, delivery_price. Связь: один пользователь — много магазинов. | ⏳ В разработке |
| Магазин и товары             | Ограничь количество магазинов по тарифу: Starter = 1, Business = 5, Premium = 10. Добавь метод canCreateMoreShops() в модель User. | ⏳ В разработке |
| Магазин и товары             | Создай API-маршрут /api/shops (POST): создаёт магазин, проверяет лимит и подписку. Верни ошибку, если лимит превышен. | ⏳ В разработке |
| Магазин и товары             | Создай модель Product: shop_id, name, price, description, category, in_stock (boolean). Добавь миграцию. | ⏳ В разработке |
| Магазин и товары             | Подключи пакет maatwebsite/excel. Создай импортёр ProductImport, который парсит CSV/XLSX с колонками: название, цена, описание, категория, наличие (1/0). | ⏳ В разработке |
| Магазин и товары             | Реализуй API-маршрут /api/shops/{shop}/import-products (POST, multipart/form-data). Принимает файл, запускает импорт, проверяет лимит товаров (пока без ограничения — заглушка). Валидируй расширение файла. | ⏳ В разработке |
| Магазин и товары             | Добавь защиту: пользователь может управлять только своими магазинами. Создай policy ShopPolicy и middleware OwnsShop. | ⏳ В разработке |
| Telegram и Web App           | Реализуй валидацию токена бота: при сохранении bot_token в магазине — отправь GET-запрос к https://api.telegram.org/bot{token}/getMe. Если ошибка — верни 422. Храни токен в БД с шифрованием (используй Crypt::encryptString()). | ⏳ В разработке |
| Telegram и Web App           | Настрой Nginx: все запросы к /app должны отдавать index.html вашего Vue-приложения (SPA fallback). Убедись, что https://ec-dn.ru/app?shop=123 открывает Web App. | ⏳ В разработке |
| Telegram и Web App           | Создай базовый Vue-компонент WebApp.vue. При загрузке — читает ?shop=id из URL, делает запрос к /api/shops/{id}/public и показывает название магазина. Подключи window.Telegram.WebApp.ready(). | ⏳ В разработке |
| Telegram и Web App           | Реализуй каталог товаров: GET /api/shops/{shop}/products → возвращает список. Во Vue — отобрази карточки товаров (название, цена, кнопка «В корзину»). | ⏳ В разработке |
| Telegram и Web App           | Создай клиентскую корзину (в localStorage): добавление, удаление, изменение количества. Не отправляй на сервер — пока только UI. | ⏳ В разработке |
| Telegram и Web App           | Реализуй страницу оформления заказа во Vue: поля ФИО, телефон, выбор способа доставки (берётся из shop.delivery_name и shop.delivery_price). Кнопка «Оплатить». | ⏳ В разработке |
| Telegram и Web App           | Создай модель Order: shop_id, customer_name, phone, total, delivery_name, delivery_price, status (pending/paid/cancelled), yookassa_payment_id. | ⏳ В разработке |
| Платежи и уведомления        | Подключи SDK ЮKassa (yoomoney/yookassa-sdk-php). Создай конфиг: YOOKASSA_SHOP_ID, YOOKASSA_SECRET_KEY в .env. | ⏳ В разработке |
| Платежи и уведомления        | Реализуй API-маршрут /api/orders (POST): создаёт черновик заказа, затем создаёт платёж в ЮKassa (метод оплаты — bank_card). Верни confirmation_url клиенту. | ⏳ В разработке |
| Платежи и уведомления        | Во Vue: после получения confirmation_url — вызови Telegram.WebApp.openInvoice(url) (или openLink, если инвойс не поддерживается). После возврата — обнови статус заказа. | ⏳ В разработке |
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

> 💡 **Примечание**: Статус "Выполнено" (✅) — только для Дня 1. Остальные — "В разработке" (⏳).
