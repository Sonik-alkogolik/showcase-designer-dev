# Telegram Webhook Through Nginx Reverse Proxy (Single Plan)

## Статус
- Этот документ заменяет оба файла:
  - `TELEGRAM_POLLING_EXECUTION_PLAN.md`
  - `TELEGRAM_POLLING_RUNBOOK.md`
- Реализация начата после подтверждения.

## Цель
Убрать зависимость от polling и стабилизировать входящий webhook Telegram через отдельный reverse-proxy контур в Nginx.

## Контекст и ограничения
- Текущий прод-контур: `Nginx -> PHP-FPM (Laravel public/index.php)`.
- Домен: `e-tgo.ru`.
- Идём без Apache (в вашей текущей архитектуре это лишний слой и лишняя точка отказа).
- Сохраняем действующий SPA/API роутинг, меняем только путь Telegram webhook.

## Целевая архитектура
1. Внешний TLS-терминатор: Nginx (`443`).
2. Отдельный location для webhook Telegram:
   - `/api/telegram/webhook`
   - отдельные таймауты/лимиты/лог.
3. Проксирование webhook в выделенный внутренний upstream (reverse proxy):
   - `proxy_pass http://127.0.0.1:8080;`
4. Внутренний backend endpoint на `127.0.0.1:8080`:
   - либо отдельный Nginx server (loopback-only) с `php-fpm`,
   - либо существующий backend-процесс (если уже есть безопасный local listener).
5. Telegram webhook указывает только на внешний URL:
   - `https://e-tgo.ru/api/telegram/webhook`.

## Почему reverse proxy вместо polling
1. Сохраняем push-модель Telegram (без постоянного worker).
2. Уменьшаем риск таймаутов за счёт выделенного маршрута и параметров Nginx.
3. Упрощаем наблюдаемость: отдельные access/error логи для webhook-трафика.

## План выполнения (после вашего подтверждения)

### Phase 1: Design Freeze
1. Зафиксировать внутренний upstream:
   - `127.0.0.1:8080`.
2. Зафиксировать location webhook с отдельными параметрами:
   - `proxy_connect_timeout 5s`;
   - `proxy_send_timeout 30s`;
   - `proxy_read_timeout 30s`;
   - `client_max_body_size 1m`.
3. Зафиксировать набор заголовков:
   - `X-Real-IP $remote_addr`;
   - `X-Forwarded-For $proxy_add_x_forwarded_for`;
   - `Host $host`;
   - `X-Forwarded-Proto $scheme`;
   - `X-Forwarded-Port $server_port`.

#### Phase 1 Gate
1. Есть финальный конфиг-блок внешнего Nginx.
2. Есть финальный конфиг внутреннего loopback listener.
3. Зафиксированы rollback-команды.

Статус: `PASS` (параметры зафиксированы в этом файле и в шаблонах `infra/nginx/*`).

### Phase 2: Nginx Config Implementation
1. Вынести текущий боевой конфиг в backup.
2. Добавить отдельный `location = /api/telegram/webhook` во внешний server block.
3. Поднять внутренний listener на `127.0.0.1:8080` (через Nginx+php-fpm).
4. Проверить синтаксис и reload:
   - `nginx -t`
   - `systemctl reload nginx`

#### Phase 2 Gate
1. `curl -I https://e-tgo.ru/api/telegram/webhook` отвечает от внешнего Nginx.
2. `curl -i http://127.0.0.1:8080/api/telegram/webhook` доступен локально.
3. В логах webhook нет 5xx на базовой проверке.

### Phase 3: Telegram Switch and Validation
1. Обновить webhook в Telegram:
   - `setWebhook https://e-tgo.ru/api/telegram/webhook`
2. Проверить `getWebhookInfo`:
   - `last_error_date`, `last_error_message`, `pending_update_count`.
3. Пройти e2e flow:
   - generate-token;
   - `/start <token>`;
   - `telegram_linked = true`.

#### Phase 3 Gate
1. `setWebhook` -> `ok=true`.
2. `pending_update_count` не растёт бесконечно.
3. E2E привязка проходит стабильно.

### Phase 4: Stabilization (24-48h)
1. Мониторинг 4xx/5xx по webhook location.
2. Мониторинг ошибок в Laravel логах webhook-контроллера.
3. Фиксация p95 latency входящих webhook.

#### Phase 4 Gate
1. Нет критических ошибок.
2. Нет повторяющихся таймаутов Telegram.
3. SLA привязки в допустимых пределах.

## Точный конфиг-контур (черновик для внедрения после подтверждения)

### Внешний Nginx (443)
```nginx
location = /api/telegram/webhook {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;

    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Port $server_port;

    proxy_connect_timeout 5s;
    proxy_send_timeout 30s;
    proxy_read_timeout 30s;
    client_max_body_size 1m;

    access_log /var/log/nginx/telegram_webhook_access.log;
    error_log  /var/log/nginx/telegram_webhook_error.log warn;
}
```

### Внутренний Nginx (loopback only)
```nginx
server {
    listen 127.0.0.1:8080;
    server_name _;

    root /var/www/showcase-designer/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Rollback
1. Вернуть backup конфига внешнего Nginx.
2. Отключить внутренний loopback server.
3. `nginx -t && systemctl reload nginx`.
4. Проверить `getWebhookInfo` и при необходимости повторно вызвать `setWebhook`.

## Артефакты реализации в репозитории
1. `infra/nginx/e-tgo.ru.external.conf.example`
2. `infra/nginx/showcase-designer-internal-loopback.conf.example`
3. `app/Console/Commands/Telegram/WebhookInfo.php`
4. `app/Console/Commands/Telegram/DeleteWebhook.php`
