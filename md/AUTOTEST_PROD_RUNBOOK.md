# AUTOTEST PROD RUNBOOK

Обновлено: 2026-04-19

## Цель

Запускать безопасные автотесты с локальной машины против `https://e-tgo.ru`, чтобы не проверять релиз вручную каждый раз.

## 1. Подготовка локальной среды

```bash
cd C:\Users\admin\Desktop\myproject\showcase-designer
python -m venv .venv
.\.venv\Scripts\activate
pip install -r tools/requirements-autotest.txt
```

## 2. Подготовка env для прод-smoke

Создать файл `tools/autotests/.env.prod` по шаблону:

```bash
copy tools\autotests\.env.prod.example tools\autotests\.env.prod
```

Заполнить значения:

- `AUTO_BASE_URL=https://e-tgo.ru`
- `AUTO_TEST_EMAIL=<тестовый email>`
- `AUTO_TEST_PASSWORD=<тестовый пароль>`
- `AUTO_TEST_SHOP_ID=<id тестового магазина>`
- `AUTO_TEST_TIMEOUT=20`
- `AUTO_TEST_BOT_TOKEN=<токен тестового бота>`
- `AUTO_TEST_CHAT_ID=<telegram chat id для теста>`
- `AUTO_TEST_TELEGRAM_USERNAME=<username для relink теста>`
- `AUTO_TEST_ALLOW_MUTATION=0` (по умолчанию)

## 3. Запуск тестов

```bash
.\.venv\Scripts\activate
python tools/run_prod_smoke.py --env-file tools/autotests/.env.prod
```

Альтернатива прямым pytest:

```bash
pytest -m prod_smoke -q tools/autotests
```

## 3.1 Mutation-тесты (добавить/удалить товар, отвязать/привязать Telegram, проверить бота)

Включать только для выделенного тестового аккаунта и магазина.

1. В `.env.prod` выставить:

```env
AUTO_TEST_ALLOW_MUTATION=1
AUTO_TEST_BOT_TOKEN=...
AUTO_TEST_CHAT_ID=...
AUTO_TEST_TELEGRAM_USERNAME=...
```

2. Запуск:

```bash
python tools/run_prod_smoke.py --env-file tools/autotests/.env.prod --include-mutation
```

3. После прогона вернуть обратно:

```env
AUTO_TEST_ALLOW_MUTATION=0
```

## 4. Результаты

Артефакты после запуска:

- `tools/autotest-reports/prod-smoke-junit.xml`
- `tools/autotest-reports/public_root.json`
- `tools/autotest-reports/auth_profile_smoke.json`
- `tools/autotest-reports/public_shop.json`
- `tools/autotest-reports/mutation_shop_bot_product.json` (если mutation включен)
- `tools/autotest-reports/mutation_telegram_relink.json` (если mutation включен)

## 5. Что делать при падении

1. Скопировать логи сервера:

```bash
tail -n 120 /var/www/showcase-designer/storage/logs/laravel.log
```

2. Приложить:
- текст падения pytest
- JSON из `tools/autotest-reports`
- свежий `laravel.log`

3. Зафиксировать результат в отчете (см. `md/TEST_REPORT_TEMPLATE.md`).

## Ограничения безопасности

`prod_smoke` должен оставаться безопасным:

- без удаления данных;
- без массовых изменений;
- только smoke для критичных endpoints.

`prod_mutation` запускать только:

- на выделенном тестовом аккаунте;
- на тестовом магазине;
- с явным подтверждением (`AUTO_TEST_ALLOW_MUTATION=1`).
