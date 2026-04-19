# Горячий фикс: profile не должен падать из-за Telegram avatar sync

Обновлено: 2026-04-19  
Файл правок: `Edits_19.04.2026_м_03.md`

## Проблема

После успешного `POST /api/login` мог падать `GET /api/profile` (500), из-за чего пользователь визуально "не входил" в аккаунт.

## Исправление

В `TelegramAvatarService::ensureUserAvatar()` добавлен защитный `try/catch`:
- любые ошибки синка аватара теперь логируются как warning;
- endpoint профиля не падает из-за внешнего Telegram/API/DB edge-case;
- возвращается `null`, а пользователь продолжает вход нормально.

## Файл

- `app/Services/TelegramAvatarService.php`

