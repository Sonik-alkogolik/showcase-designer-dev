# Telegram Webhook Runbook (Simplified)

## Статус
- Этот файл — единый план по Telegram webhook.
- Схема упрощена: без отдельного внутреннего `127.0.0.1:8080` и без `proxy_pass`.

## Цель
Стабилизировать работу Telegram webhook через текущий маршрут Laravel `/api/telegram/webhook` и исходящий proxy для Telegram API.

## Финальная схема
1. Входящий webhook:
   - Telegram -> `https://e-tgo.ru/api/telegram/webhook` -> текущий `location /api/` -> Laravel.
2. Исходящие запросы к Telegram API:
   - Laravel -> `api.telegram.org` через `TELEGRAM_HTTP_PROXY=http://151.247.209.157:1050`.

## Что не используем
1. Отдельный reverse-proxy маршрут `location = /api/telegram/webhook { proxy_pass ... }`.
2. Отдельный внутренний Nginx listener на `127.0.0.1:8080`.

## Phase 1: Server Update
1. Обновить код на сервере:
```bash
cd /var/www/showcase-designer
git pull --ff-only
composer dump-autoload -o
```

2. Пересобрать и опубликовать frontend (обязательно, иначе UI может остаться старым):
```bash
cd /var/www/showcase-designer/client
npm run build

cd /var/www/showcase-designer
rm -f public/index.html
rm -rf public/assets
cp client/dist/index.html public/
cp -r client/dist/assets public/
```

3. Очистить/пересобрать кеши Laravel:
```bash
cd /var/www/showcase-designer
php artisan optimize:clear
php artisan config:cache
```

## Phase 2: Env Verification
1. Проверить `.env`:
```dotenv
TELEGRAM_HTTP_PROXY=http://151.247.209.157:1050
```
2. Применить конфиг:
```bash
php artisan optimize:clear
php artisan config:cache
```

## Phase 3: Webhook Validation
1. Проверить endpoint:
```bash
curl -i https://e-tgo.ru/api/telegram/webhook
```
2. Переустановить webhook:
```bash
php artisan telegram:set-webhook "https://e-tgo.ru/api/telegram/webhook"
php artisan telegram:webhook-info
```
3. Пройти e2e flow:
   - generate-token;
   - `/start <token>`;
   - профиль показывает `telegram_linked = true`.

## Phase 4: Stabilization
1. Мониторинг 24-48 часов:
   - ошибки webhook в Laravel логе;
   - значения `pending_update_count` и `last_error_message` через `telegram:webhook-info`.
2. При проблемах:
```bash
php artisan telegram:delete-webhook
php artisan telegram:set-webhook "https://e-tgo.ru/api/telegram/webhook"
php artisan telegram:webhook-info
```

## Rollback
1. Вернуть предыдущий commit приложения (если нужно).
2. Повторно применить `config:cache`.
3. Проверить webhook командой `telegram:webhook-info`.
