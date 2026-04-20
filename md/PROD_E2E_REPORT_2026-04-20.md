# PROD E2E Report — 2026-04-20

Стенд: `https://e-tgo.ru`  
UI runner: `http://127.0.0.1:7861`

## Итоговый полный прогон

Команда:

```powershell
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773720 --bot-token *** --plan starter --headless
```

Результат:

- `REGISTER_OK: True`
- `TELEGRAM_LINK_OK: True`
- `PLAN_OK: True (starter)`
- `CREATE_SHOP_OK: True`
- `CONNECT_BOT_OK: True`
- `ADD_PRODUCT_OK: True`
- `IMPORT_PRODUCT_OK: False`
- `DELETE_ACCOUNT_OK: True`

Артефакты:

- `tools/e2e_prod_real_user_full_report.json`
- `tools/e2e_prod_real_user_full.png`

## Что обнаружено

1. Импорт товара недоступен на `starter`.
   - Кнопка импорта в UI disabled.
   - В отчете: `"error": "Import button is disabled (plan/capability restriction)"`.
   - Это ожидаемое ограничение тарифа, не падение бекенда.

2. При прогоне с боевым `chat_id 954773719` привязка Telegram не проходит.
   - Вероятная причина: `chat_id` уже привязан к другому пользователю (бизнес-ограничение в webhook-логике).

3. При запросе `business` на проде в некоторых прогонах API возвращал `starter`.
   - Требует отдельной проверки бизнес-логики тарифа/платежного флоу.

## Что исправлено в автотесте

Файл: `tools/e2e_prod_real_user_full.py`

- Добавлен fallback получения Telegram токена через API, если ссылка не считалась из UI.
- Исправлен невалидный Playwright-селектор в шаге импорта.
- Улучшена проверка ответа webhook (`success` и `ok`) + запись деталей в report.
- Добавлена проверка привязки Telegram через `/api/profile`, а не только через текст в UI.
- Исправлена отправка webhook payload как JSON-строки (`Content-Type: application/json`).

