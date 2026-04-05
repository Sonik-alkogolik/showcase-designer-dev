# Telegram Polling Execution Plan

## Goal
Перейти с `webhook` на устойчивый `long polling` для Telegram Bot API в условиях нестабильного входящего канала `Telegram -> сервер`.

## Why This Plan
- Исходящие запросы `сервер -> api.telegram.org` уже работают через proxy.
- Входящий webhook периодически недоступен (timeout со стороны Telegram).
- Polling убирает зависимость от входящих соединений на сервер.

## Target Architecture
1. Один фоновый polling-worker.
2. `getUpdates` в режиме long polling (`timeout 30-50s`).
3. Хранение `offset` в кэше/БД.
4. Переиспользование текущей бизнес-логики привязки (`/start <token>`), чтобы не дублировать правила.
5. Медленный idle-режим при отсутствии активности.
6. Ускоренный режим, когда пользователь только что сгенерировал токен привязки.

## Execution Phases

## Delivery Flow (Project Standard)
1. Все изменения делаем локально.
2. Коммитим атомарно (по одной фазе/подзадаче).
3. Пушим в `showcase-designer-dev` (`origin`).
4. После проверки пушим в `showcase-designer` (`prod`).
5. На удаленном сервере выполняем `git pull`.
6. После `pull` обязательно:
   - `composer dump-autoload -o`
   - `php artisan optimize:clear`
   - `php artisan config:cache`
   - перезапуск процессов (`queue:restart` и/или supervisor reload для polling worker).
7. Проверяем e2e только после применения кэшей/процессов на сервере.

## Phase 1: Design Freeze
1. Зафиксировать формат хранения `offset`:
   - ключ: `telegram_updates_offset_<botname>`;
   - значение: последний `update_id + 1`.
2. Зафиксировать режимы опроса:
   - `active`: `timeout=30`, пауза 0-1s;
   - `idle`: `timeout=50`, пауза 5-15s.
3. Зафиксировать источник сигнала "active":
   - после `generate-token` ставить флаг активности на 15 минут.

## Phase 2: Backend Changes
1. Создать сервис обработки входящих update (общий для webhook/polling).
2. Вынести логику из `WebhookController` в сервис:
   - парсинг `/start`;
   - привязка аккаунта по `token`;
   - проверка `token` в cache;
   - ответные сообщения.
3. Добавить `artisan` команду `telegram:poll-updates`:
   - читает `offset`;
   - вызывает `getUpdates`;
   - обрабатывает список updates через общий сервис;
   - сдвигает `offset`.
4. Добавить базовые метрики в лог:
   - `updates_received`;
   - `updates_processed`;
   - `poll_errors`;
   - текущий режим `active/idle`.

## Phase 3: Runtime Orchestration
1. Запуск worker через `supervisor` или `systemd` (предпочтительно `supervisor`).
2. Гарантировать единственный экземпляр polling-worker.
3. Настроить автоперезапуск при падении.
4. Установить ограничение памяти/времени перезапуска процесса.

## Phase 4: Telegram Mode Switch
1. Отключить webhook в Telegram (`deleteWebhook`).
2. Включить polling-worker.
3. Проверить, что `pending_update_count` уменьшается.
4. Подтвердить привязку пользователя через UI:
   - клик `Подключить Telegram`;
   - переход в бот;
   - `/start <token>`;
   - `telegram_linked = true`.

## Phase 5: Stabilization
1. Наблюдать 24-48 часов:
   - ошибки Telegram API;
   - средняя задержка привязки;
   - дубли update.
2. При необходимости скорректировать `timeout`/паузы.
3. Закрыть phase только после стабильного e2e.

## Operational Profile (Low Load)
1. Не использовать `cron` каждые 10 секунд.
2. Использовать long polling (1 запрос может ждать до 30-50 секунд).
3. В idle режиме фактическая частота запросов низкая.
4. Нагрузка минимальна даже при нулевой активности пользователей.

## Risks and Mitigations
1. Дубли апдейтов:
   - строгий `offset`;
   - идемпотентная обработка `update_id`.
2. Потеря апдейтов при рестарте:
   - сохранять `offset` сразу после обработки батча.
3. Два воркера одновременно:
   - singleton запуск через supervisor/group config.
4. Временные ошибки proxy:
   - retry с backoff;
   - логирование причин.

## Rollback Plan
1. Остановить polling-worker.
2. Вернуть webhook (`setWebhook`) если входящий канал восстановлен.
3. Переключить обработку на старый путь без удаления сервиса (чтобы быстро вернуться назад).

## Definition of Done
1. Привязка Telegram работает без webhook.
2. Доля успешных привязок стабильная.
3. Нет критичных дублей/потерь updates.
4. Нагрузка сервера в idle режиме приемлемая.

## Next Session Checklist
1. Создать общий `UpdateProcessor` сервис.
2. Подключить его в `WebhookController`.
3. Добавить команду `telegram:poll-updates`.
4. Подготовить `supervisor` конфиг.
5. Прогнать e2e на production в controlled window.
