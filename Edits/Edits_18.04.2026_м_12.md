# Этап 11: фикс dropdown пользователя в edit подписки (MoonShine)

Обновлено: 2026-04-18  
Файл правок: `Edits_18.04.2026_м_12.md`

## Проблема

В форме редактирования подписки поле `Пользователь` продолжало показываться как выпадающий список.

## Причина

В `SubscriptionFormPage` режим редактирования определялся через:
- `request()->route('resourceItem') instanceof Subscription`

Но в MoonShine параметр `resourceItem` часто приходит как ID (строка), а не как модель.  
Из-за этого проверка возвращала `false`, и форма работала как create-режим.

## Что исправлено

В `SubscriptionFormPage` проверка режима edit заменена на:
- `! empty($resourceItem)`

Теперь:
- в edit поле пользователя read-only;
- в create остаётся select выбора пользователя.

## Файл

- `app/MoonShine/Resources/Subscription/Pages/SubscriptionFormPage.php`

