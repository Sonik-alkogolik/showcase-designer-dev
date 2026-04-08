# Project Focus

## Active Goal
`Стабилизировать core-flow сервиса после успешного восстановления Telegram webhook/linking на проде`

## Current Step
`Зафиксировать рабочее состояние Telegram linking на проде и перейти к полному ручному core-flow прогону`

## Next Action
`Пройти ручной сценарий: register -> link Telegram -> create shop -> add category/product -> delete account -> repeat, и записать блокеры в CHAT_HANDOFF.md`

## Source Of Truth
- Статус "где остановились" ведем только в `CHAT_HANDOFF.md`.
- Этот файл хранит только текущий фокус сессии.

## Next Tile (tools/dev_ui.py -> taskTiles)
title: `Core flow full pass`
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
- Telegram webhook восстановлен и стабилизирован на `https://e-tgo.ru/api/telegram/webhook`
- Telegram linking подтвержден в проде: `/start {token}` -> успешная привязка
