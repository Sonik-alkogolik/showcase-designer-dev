# Автоматизированный prod smoke на Python (pytest)

Обновлено: 2026-04-19  
Файл правок: `Edits_19.04.2026_м_08.md`

## Что добавлено

1. Python autotest suite (безопасный для прод):
- `tools/autotests/test_prod_smoke_api.py`
- marker: `prod_smoke`
- проверки:
  - доступность `/`
  - `login -> profile -> shops -> subscription/plans -> logout`
  - публичный endpoint магазина `/api/shops/{id}/public`

2. Конфигурация и запуск:
- `tools/autotests/conftest.py`
- `tools/autotests/config.py`
- `tools/run_prod_smoke.py`
- `pytest.ini`
- `tools/requirements-autotest.txt`
- шаблон env: `tools/autotests/.env.prod.example`

3. Документация:
- `md/AUTOTEST_PROD_RUNBOOK.md` (как запускать с локалки)
- `md/TEST_REPORT_TEMPLATE.md` (шаблон отчёта)

4. Удобный shortcut:
- в `scripts/dev-shortcuts.ps1` добавлен action:
  - `autotest-prod-smoke`

5. Git hygiene:
- `.gitignore` обновлён для:
  - `tools/autotest-reports/`
  - `tools/autotests/.env.prod`

