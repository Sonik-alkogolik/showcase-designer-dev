# Этап 7: правки формы магазина и hero-контейнера

Обновлено: 2026-04-18
Файл правок: `Edits_18.04.2026_м_08.md`

## Что изменено

1. В форме настроек магазина удалён текст-подсказка:
- "Важно: в @BotFather ... /setdomain".

Файл:
`client/src/views/ShopSettingsView.vue`

2. В публичном лендинге контейнер hero:
- заменён тег `<header class="hero">` на `<div class="hero">`;
- добавлены стили:
  - `padding: 0 10px;`
  - `margin: 0px auto;`

Файл:
`client/src/views/PublicLandingView.vue`

## Проверка

1. `npm run build` выполнен успешно.
