# PROJECT_AGENT_PROMPT

## 1) О чем проект
Showcase Designer: Laravel 12 + Vue 3 SaaS для создания магазинов, загрузки товаров, оформления заказов через Telegram Web App и оплаты через ЮKassa.

Ключевой сценарий MVP:
регистрация пользователя -> подписка -> создание магазина -> импорт товаров -> заказ в Telegram Web App -> оплата -> уведомления владельцу.

## 2) Текущий статус
- День 1 (API-аутентификация через Breeze + Sanctum) выполнен.
- Email-верификация как основной путь отключена.
- В проекте уже используется Telegram-link flow (привязка Telegram через бота и токен).
- Подписки, магазины, товары и импорт уже в основном реализованы.

## 3) Текущий фокус (День 2, пункт 2)
Нужно подтверждение пользователя делать через Telegram, а не через email.

Требования для реализации:
- После регистрации пользователь считается не подтвержденным, пока не привяжет Telegram.
- Доступ к защищенным маршрутам блокируется middleware `ensure.telegram.verified`.
- Подтверждение выполняется через Telegram-бота (deep link `/start <token>` или аналогичный безопасный механизм).
- Статус подтверждения хранится в `users.telegram_linked_at` (или `telegram_verified_at`, если выбрана старая схема).

Критерий готовности:
- Новый пользователь не может выполнять защищенные действия до Telegram-подтверждения.
- После подтверждения через бота доступ открывается без участия email.

## 4) Технический контекст
- Backend: Laravel 12, Sanctum, MoonShine.
- Frontend: Vue 3 (SPA), токен хранится в `localStorage`.
- Основные API-маршруты: `POST /api/register`, `POST /api/login`, `POST /api/logout`.
- Telegram webhook: `POST /api/telegram/webhook`.
- Middleware проверки: `App\Http\Middleware\EnsureTelegramVerified`.

## 5) Локальные пути и сборка (Windows)
Использовать локальные пути проекта, а не серверные `/var/www/...`.

```powershell
cd C:\Users\admin\Desktop\myproject\showcase-designer-dev\client
npm run build

cd C:\Users\admin\Desktop\myproject\showcase-designer-dev
Remove-Item -Path public\index.html -Force -ErrorAction SilentlyContinue
Remove-Item -Path public\assets -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item -Path client\dist\index.html -Destination public\
Copy-Item -Path client\dist\assets -Destination public\assets -Recurse
```

## 6) Правила для дальнейших задач
- Не возвращаться к email-верификации, если нет явного требования.
- Все проверки доступа к функционалу магазина вести через подписку + Telegram-подтверждение.
- Любые изменения в auth-flow синхронизировать между:
  - `routes/api.php`
  - `RegisteredUserController`
  - `ProfileController`
  - `WebhookController`
  - `EnsureTelegramVerified`
  - фронтенд (`useAuth.js`, `ProfileView.vue`, `LoginView.vue`, `RegisterView.vue`)

## 7) Правило Task-Driven QA UI (обязательно)
Цель: при любой доработке сразу добавлять тест-кнопки в локальный web UI, чтобы проверка шла непрерывно по плану задач.

Источник UI:
- `tools/dev_ui.py` -> блок `taskTiles` в HTML `<script>`.

Обязательный формат одной плитки:
```js
{
  title: 'Короткое имя теста',
  sub: 'Что именно проверяет тест',
  plan: 'Раздел плана (например Auth API / Shops API / Orders API)',
  cmd: 'Команда или запрос для запуска'
}
```

Правила обновления:
- Новые плитки добавлять в конец массива `taskTiles`: UI сам выводит их в обратном порядке (`newest first`), поэтому новые будут сверху.
- Для каждой новой backend-фичи (особенно POST/PUT/PATCH/DELETE) добавлять минимум 1 плитку теста успешного сценария.
- Для критичных фич добавлять минимум 2 плитки: success + negative (ошибка валидации/доступа).
- Если ручка требует токен, предусматривать тест через `/api/http` с Bearer token (или отдельный шаг получения токена).
- После любого изменения API сначала обновить/добавить плитки, потом выполнять ручной smoke-test через UI.

Минимальный чек при завершении задачи:
- Есть плитка(и) для новой задачи в `taskTiles`.
- Тест из плитки реально выполняется и даёт ожидаемый HTTP-статус.
- Результат проверки виден в блоке `Output` UI.
