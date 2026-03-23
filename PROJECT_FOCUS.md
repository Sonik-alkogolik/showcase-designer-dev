# Project Focus

## Active Goal
`Стабилизировать core-flow сервиса (auth -> shop -> products -> webapp)`

## Current Step
`Собираем и чиним узкие места в базовом пользовательском пути`

## Next Action
`Пройти чек-лист core-flow и зафиксировать результаты в CHAT_HANDOFF.md`

## Source Of Truth
- Статус "где остановились" ведем только в `CHAT_HANDOFF.md`.
- Этот файл хранит только текущий фокус сессии.

## Next Tile (tools/dev_ui.py -> taskTiles)
title: `Core-flow smoke`
sub: `auth -> create shop -> products -> webapp`
plan: `Service Core`
cmd: `ручной проход + e2e smoke`

## Done When
- Базовый путь работает без блокирующих ошибок
- Ошибки/блокеры записаны в CHAT_HANDOFF.md
- Следующий шаг на завтра сформулирован в 1-2 пунктах

## Session Notes
- Фокус на сервисе, не на маркетинговых лендингах
- Лендинг/конверсия вынесены в следующий этап
