# Project Focus

## Active Goal
`Стабилизировать core-flow сервиса (auth -> shop -> products -> webapp)`

## Current Step
`Стабилизация infra для Telegram smoke: выровнять DB_HOST (127.127.126.50 vs .26) + дожать public smoke через tunnel`

## Next Action
`Запустить smoke-checkout-payment для shop=1 локально и через стабильный public tunnel, затем пройти ручной Telegram checkout`

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
- Следующий шаг сформулирован в 1-2 пунктах

## Session Notes
- Фокус на сервисе, не на маркетинговых лендингах
- Лендинг/конверсия вынесены в следующий этап
- Для dev-smoke использовать `.\scripts\dev-shortcuts.ps1 smoke-checkout-payment ...` как обязательный preflight перед ручным Telegram-проходом
