# Project Focus

## Active Goal
`Стабилизировать Telegram linking UX и гарантировать локальный воспроизводимый user-flow`

## Current Step
`Локально пройти сценарий: register -> link Telegram -> create shop -> add category/product -> delete account -> повтор`

## Next Action
`Поднять backend+frontend на локальной машине и сделать ручной прогон как пользователь с фиксацией результата в CHAT_HANDOFF.md`

## Source Of Truth
- Статус "где остановились" ведем только в `CHAT_HANDOFF.md`.
- Этот файл хранит только текущий фокус сессии.

## Next Tile (tools/dev_ui.py -> taskTiles)
title: `Telegram linking local pass`
sub: `register -> link -> shop -> product -> delete -> repeat`
plan: `Service Core`
cmd: `ручной user-flow + API/feature checks`

## Done When
- Базовый путь работает без блокирующих ошибок
- Ошибки/блокеры записаны в CHAT_HANDOFF.md
- Следующий шаг сформулирован в 1-2 пунктах

## Session Notes
- Фокус на сервисе, не на маркетинговых лендингах
- Лендинг/конверсия вынесены в следующий этап
- Кнопка Telegram `Открыть магазин` удалена вручную через Telegram API
- UX Telegram linking обновлен: `Подключить Telegram` + `Проверить привязку` + авто-проверка + `Скопировать ссылку`
