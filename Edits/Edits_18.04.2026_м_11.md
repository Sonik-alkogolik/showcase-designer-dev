# Этап 10: восстановление пароля (email + Telegram fallback) и обязательная смена после временного пароля

Обновлено: 2026-04-18  
Файл правок: `Edits_18.04.2026_м_11.md`

## Что реализовано

1. Экран `Забыли пароль?` во фронте:
- путь: `/forgot-password`;
- режим Email: отправка стандартного reset-письма;
- режим Telegram: отправка одноразового кода в Telegram и подтверждение кода.

2. Экран email-reset по ссылке:
- путь: `/password-reset/:token`;
- принимает `email` из query;
- отправляет новый пароль на backend (`/api/reset-password`).

3. Telegram fallback c одноразовым токеном и TTL:
- endpoint: `POST /api/forgot-password/telegram`;
- endpoint: `POST /api/reset-password/telegram`;
- токен хранится в БД в хэшированном виде (`telegram_password_reset_tokens`);
- TTL токена: 15 минут;
- токен одноразовый (`used_at`).

4. Временный пароль + обязательная смена после входа:
- при подтверждении Telegram-кода backend генерирует временный пароль;
- отправляет его в Telegram;
- у пользователя выставляется `must_change_password = true`;
- после входа frontend принудительно ведет на `/force-password-change`;
- backend middleware блокирует остальные API до смены пароля.

5. Экран обязательной смены пароля:
- путь: `/force-password-change`;
- endpoint: `POST /api/profile/password/force-change`;
- после успешной смены флаг `must_change_password` снимается.

## Ключевые изменения по файлам

1. Backend:
- `app/Http/Controllers/Auth/PasswordRecoveryController.php`
- `app/Http/Middleware/EnsurePasswordChangeCompleted.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Auth/NewPasswordController.php`
- `app/Http/Controllers/Api/ProfileController.php`
- `app/Http/Kernel.php`
- `app/Models/User.php`
- `app/Models/TelegramPasswordResetToken.php`
- `routes/api.php`
- `database/migrations/2026_04_18_200000_add_must_change_password_to_users_table.php`
- `database/migrations/2026_04_18_201000_create_telegram_password_reset_tokens_table.php`

2. Frontend:
- `client/src/views/ForgotPasswordView.vue`
- `client/src/views/PasswordResetView.vue`
- `client/src/views/ForcePasswordChangeView.vue`
- `client/src/views/LoginView.vue`
- `client/src/router/index.js`
- `client/src/composables/useAuth.js`

## Ожидаемый результат

1. Пользователь может восстановить пароль по email стандартным способом.
2. При недоступном email можно восстановить доступ через Telegram fallback.
3. Временный пароль не является постоянным: после входа система принуждает сменить его на новый.

