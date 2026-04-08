# Telegram Webhook Runbook (Simplified)

## Текущий фокус (Cloudflare Tunnel, 2026-04-06)
- Цель: принимать входящий webhook Telegram через `tg-hook.e-tgo.ru`, чтобы обойти прямые timeout на `176.113.82.151:443`.
- Статус сейчас: Tunnel создан и запущен, но зона Cloudflare еще не `Active` (NS в процессе делегирования).

### Уже сделано
1. Установлен `cloudflared` на сервере.
2. Выполнен `cloudflared tunnel login`.
3. Создан tunnel:
   - `tg-webhook`
   - `tunnel id: 6388aa0f-c49d-43f9-bb20-be4c04f6483b`
4. Создан DNS route:
   - `tg-hook.e-tgo.ru -> tunnel tg-webhook`
5. Настроен `/etc/cloudflared/config.yml`:
   - ingress `tg-hook.e-tgo.ru -> http://127.0.0.1:80`
   - `protocol: http2` (вместо quic, чтобы не упираться в UDP timeout)
6. Сервис запущен и активен:
   - `systemctl status cloudflared` -> `active (running)`

### Следующие шаги (сразу после статуса зоны Active)
1. Проверить DNS у Cloudflare:
```bash
dig +short tg-hook.e-tgo.ru @1.1.1.1
```
Ожидаем непустой ответ.

2. Проверить webhook URL через tunnel:
```bash
curl -i --max-time 15 https://tg-hook.e-tgo.ru/api/telegram/webhook
```
Ожидаем ответ Laravel (`HTTP 200`, тело `{"ok":true}` на POST).

3. Переназначить Telegram webhook на tunnel-host:
```bash
cd /var/www/showcase-designer
php artisan telegram:set-webhook "https://tg-hook.e-tgo.ru/api/telegram/webhook"
php artisan telegram:webhook-info
```

4. Проверка боевого сценария:
   - в UI сгенерировать новый токен привязки;
   - отправить в бота `/start <token>`;
   - нажать "Проверить привязку" в UI;
   - убедиться, что `telegram_linked = true`.

5. Контрольный чек:
```bash
php artisan telegram:webhook-info
```
Ожидаем:
   - `last_error_message` пустой;
   - `pending_update_count` не накапливается.

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
