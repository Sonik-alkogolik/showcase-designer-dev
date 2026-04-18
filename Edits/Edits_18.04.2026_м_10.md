# Этап 9: больше данных о пользователе в MoonShine (подписки)

Обновлено: 2026-04-18
Файл правок: `Edits_18.04.2026_м_10.md`

## Проблема

В форме подписки (`Subscription form`) пользователь отображался только как ID, из-за чего сложно выбирать нужного человека.

## Что изменено

1. В `SubscriptionFormPage` поле пользователя переведено на `Select('user_id')` с расширенными подписями:
- формат: `ID {id} | {name} | {email} | @{telegram_username}` (если username есть).

2. В `UserResource` задан человекочитаемый столбец ресурса:
- `protected string $column = 'name';`

## Файлы

1. `app/MoonShine/Resources/Subscription/Pages/SubscriptionFormPage.php`
2. `app/MoonShine/Resources/User/UserResource.php`

## Ожидаемый результат

В админке при выборе пользователя в подписке видно не только ID, но и основные идентификаторы пользователя для удобной ручной работы.
