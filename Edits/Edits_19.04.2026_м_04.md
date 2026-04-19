# Горячий фикс: middleware alias `password.change.required` на проде

Обновлено: 2026-04-19  
Файл правок: `Edits_19.04.2026_м_04.md`

## Проблема

На проде падал `GET /api/profile` с ошибкой:
- `Target class [password.change.required] does not exist.`

Из-за этого после успешного `POST /api/login` пользователь не мог войти в интерфейс.

## Исправление

В `routes/api.php` middleware для защищенной группы изменён с алиаса на прямой класс:

- было: `'password.change.required'`
- стало: `\App\Http\Middleware\EnsurePasswordChangeCompleted::class`

Это устраняет зависимость от alias-резолва на конкретном окружении.

## Файл

- `routes/api.php`

