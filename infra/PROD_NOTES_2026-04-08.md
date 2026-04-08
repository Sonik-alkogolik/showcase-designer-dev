# PROD Notes (2026-04-08)

## Telegram Webhook: Working State

- Active webhook URL: `https://e-tgo.ru/api/telegram/webhook`
- Stable Bot API webhook setup used with explicit IP:
  - `ip_address=104.21.56.199`
- Cloudflare DNS requirement for stable webhook delivery:
  - `A e-tgo.ru -> 176.113.82.151` with `Proxied` enabled

## Cloudflare / Tunnel

- Tunnel ID: `6388aa0f-c49d-43f9-bb20-be4c04f6483b`
- Hostnames involved during recovery:
  - `tg-hook.e-tgo.ru`
  - `tg-hook2.e-tgo.ru`

## Verification Commands

```bash
php artisan telegram:webhook-info
```

Expected:
- `Webhook URL` is `https://e-tgo.ru/api/telegram/webhook`
- `last_error_message` is empty

## Security Follow-up (Required)

- Revoke exposed bot token in `@BotFather`
- Issue a new token
- Update production `.env`:
  - `TELEGRAM_BOT_TOKEN=<new_token>`
- Apply config refresh:

```bash
php artisan optimize:clear
php artisan config:cache
```
