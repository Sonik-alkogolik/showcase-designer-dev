# Security Debt TODO

Обновлено: 2026-03-30

Цель: зафиксировать уязвимости, которые закрываем после завершения текущего функционального блока.

## Правило работы

- Этот файл = backlog по security-долгу.
- После закрытия пункта: помечаем `✅` и добавляем ссылку на коммит/PR.

## Пункты к устранению

1. [ ] **Critical**: принудительно включить проверку Telegram WebApp `initData` для публичного checkout.
   - Где: `config/security.php`, `.env`, `routes/api.php`, `app/Http/Middleware/VerifyTelegramWebAppInitData.php`
   - Риск: поддельные публичные заказы и спам уведомлений.
   - Done: `TELEGRAM_INITDATA_ENFORCE=true` на prod, тесты на валидный/невалидный `initData`.

2. [ ] **High**: убрать риск смены владельца магазина через mass assignment.
   - Где: `app/Models/Shop.php`, `app/Http/Controllers/ShopController.php`
   - Риск: изменение `user_id` через `update($request->all())`.
   - Done: `user_id` не массово присваивается, update идёт по whitelisted полям, есть feature-test.

3. [ ] **High**: добавить верификацию webhook ЮKassa (подпись/секрет/доп. защита).
   - Где: `routes/api.php`, `app/Http/Controllers/OrderController.php`
   - Риск: публичный endpoint можно дергать фейковыми событиями/нагрузкой.
   - Done: webhook принимает только валидно подписанные запросы, негативные тесты добавлены.

4. [ ] **Medium**: исключить утечку `bot_token` из API-ответов магазина.
   - Где: `app/Models/Shop.php`, `app/Http/Controllers/ShopController.php`
   - Риск: токен бота может попасть во frontend/логи.
   - Done: `bot_token` скрыт в сериализации (`hidden`) и не возвращается в `store/update` ответах.

5. [ ] **Medium**: ограничить/защитить публичную проверку статуса оплаты.
   - Где: `routes/api.php`, `app/Http/Controllers/OrderController.php`
   - Риск: утечка статусов по `paymentId` при подборе.
   - Done: endpoint защищён токеном/подписью или заменён безопасным polling-механизмом.

6. [ ] **Medium**: добавить верификацию источника Telegram webhook.
   - Где: `routes/api.php`, `app/Http/Controllers/Telegram/WebhookController.php`
   - Риск: поддельные webhook-запросы.
   - Done: проверка webhook-secret/IP allowlist и тесты на reject без секрета.

7. [ ] **Medium**: добавить rate limit на регистрацию.
   - Где: `routes/api.php`, `app/Providers/AppServiceProvider.php`
   - Риск: массовые регистрации и мусорные аккаунты.
   - Done: limiter на `/api/register`, покрыт тестом на `429`.

## Примечание

- Пользовательский API не должен и не может менять `.env`.
- Любые изменения `.env` разрешены только вручную оператором/DevOps.
