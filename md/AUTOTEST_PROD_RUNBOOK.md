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

## 3. Запуск тестов

```bash
.\.venv\Scripts\activate
python tools/run_prod_smoke.py --env-file tools/autotests/.env.prod
```

Альтернатива прямым pytest:

```bash
pytest -m prod_smoke -q tools/autotests
```

## 4. Результаты

Артефакты после запуска:

- `tools/autotest-reports/prod-smoke-junit.xml`
- `tools/autotest-reports/public_root.json`
- `tools/autotest-reports/auth_profile_smoke.json`
- `tools/autotest-reports/public_shop.json`

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

