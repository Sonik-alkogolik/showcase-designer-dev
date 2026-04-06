# Quick Commands

Ниже короткий набор команд для ежедневной работы.  
Запускать из PowerShell в корне проекта.

## Быстрый старт

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
.\scripts\dev-shortcuts.ps1 help
```

## Готовые команды

```powershell
# Открыть проект в VS Code
.\scripts\dev-shortcuts.ps1 open-project

# Открыть приложение в браузере
.\scripts\dev-shortcuts.ps1 open-browser

# Открыть страницу логина
.\scripts\dev-shortcuts.ps1 open-login

# Проверить API логин (по умолчанию test@example.com / password)
.\scripts\dev-shortcuts.ps1 api-login

# Проверить API логин с вашими данными
.\scripts\dev-shortcuts.ps1 api-login -Email "your@email.com" -Password "your_password"

# Проверить подключение к БД
.\scripts\dev-shortcuts.ps1 db-ping

# Поднять MySQL-8.0 через OSPanel и сразу проверить подключение
.\scripts\dev-shortcuts.ps1 db-up

# Запустить Laravel dev server (foreground, держите окно открытым)
.\scripts\dev-shortcuts.ps1 serve

# Поднять localtunnel в фоне и вывести URL
.\scripts\dev-shortcuts.ps1 tunnel-up

# Закрепить Telegram на URL из tools/tmp-lt.out
.\scripts\dev-shortcuts.ps1 telegram-pin-current-tunnel -ShopId "2"

# Закрепить Telegram dev (обновит APP_URL/FRONTEND_URL/WEBHOOK в .env, выставит webhook и кнопку WebApp)
.\scripts\dev-shortcuts.ps1 telegram-pin-dev

# То же, но с явным URL туннеля и shop id
.\scripts\dev-shortcuts.ps1 telegram-pin-dev -PublicUrl "https://showcase-dev-20260321.loca.lt" -ShopId "2"

# Отправить в Telegram тест-кнопку "Открыть магазин" (WebApp) в конкретный chat_id
.\scripts\dev-shortcuts.ps1 telegram-send-webapp-test -ChatId "123456789" -ShopId "2"

# Smoke checkout/payment (API): создаёт заказ, пытается payment, делает fallback в draft при недоступной ЮKassa
.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -ShopId "2"

# То же, но дополнительно проверяет внешний /app через tunnel URL
.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -PublicUrl "https://showcase-dev-20260321.loca.lt" -ShopId "2"

# Быстрый тест создания магазина (Feature test)
.\scripts\dev-shortcuts.ps1 test-shop-create

# Browser E2E: логин + создание магазина + добавление товара + проверка в списках
.\scripts\dev-shortcuts.ps1 e2e-create-shop

# Browser E2E в видимом Chrome (можно наблюдать шаги)
.\scripts\dev-shortcuts.ps1 e2e-auth-login-chrome
.\scripts\dev-shortcuts.ps1 e2e-create-shop-chrome

# Browser E2E в режиме "реальный пользователь" (медленный ввод + паузы)
.\scripts\dev-shortcuts.ps1 e2e-auth-login-real-user
.\scripts\dev-shortcuts.ps1 e2e-create-shop-real-user

# Полный E2E прогон "как реальный пользователь" (в один запуск)
.\scripts\dev-shortcuts.ps1 e2e-full-real-user
# В этом режиме Chrome не закрывается автоматически после финального шага.

# Открыть MySQL shell (showcase_designer)
.\scripts\dev-shortcuts.ps1 db-shell

# Запустить web UI быстрых команд
.\scripts\dev-shortcuts.ps1 start-ui
```

## Быстрый рабочий цикл для Telegram WebApp

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
.\scripts\dev-shortcuts.ps1 db-up
.\scripts\dev-shortcuts.ps1 serve
# в новом окне:
.\scripts\dev-shortcuts.ps1 tunnel-up
# затем:
.\scripts\dev-shortcuts.ps1 telegram-pin-current-tunnel -ShopId "2"
# smoke checkout/payment (в новом окне):
.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -ShopId "2"
```

Если в Telegram появляется экран localtunnel с паролем, используйте:
`151.247.209.157`

Если снова `503 - Tunnel Unavailable`, просто повторите:
1. `.\scripts\dev-shortcuts.ps1 tunnel-up`
2. `.\scripts\dev-shortcuts.ps1 telegram-pin-current-tunnel -ShopId "2"`

## Telegram webhook (prod/admin)

```powershell
# Установить webhook (если URL уже в TELEGRAM_WEBHOOK_URL)
php artisan telegram:set-webhook

# Установить webhook на явный URL
php artisan telegram:set-webhook "https://e-tgo.ru/api/telegram/webhook"

# Посмотреть текущее состояние webhook
php artisan telegram:webhook-info

# Удалить webhook
php artisan telegram:delete-webhook

# Удалить webhook и сбросить pending updates
php artisan telegram:delete-webhook --drop-pending
```

После запуска `start-ui` откроется страница:
`http://127.0.0.1:8787`

Во вкладке **Tests** доступны плитки:
1. `E2E Full Suite (Real User Chrome)` — единый комплексный прогон (логин + создание магазина + добавление товара) в один клик.
2. Следующий шаг для расширения full-suite: smoke-оформление заказа.

В UI есть блок **HTTP Test Lab**:
1. `Preset: test-cors` — проверка `GET /api/test-cors`
2. `Preset: login` — проверка `POST /api/login`
3. `Preset: profile` — проверка `GET /api/profile` (нужен Bearer token)
4. Можно вручную задать Method/URL/Body/Token и отправить запрос кнопкой `Send Request`.

## Как добавлять свои команды

1. Откройте файл `scripts/dev-shortcuts.ps1`.
2. Добавьте новый `switch`-блок по шаблону:

```powershell
"my-command" {
    # TODO: your command
    break
}
```

3. Добавьте строку с описанием в блок `help`.
