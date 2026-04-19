# Mutation автотесты для прод-тестового аккаунта (локально, без push в prod)

Обновлено: 2026-04-19  
Файл правок: `Edits_19.04.2026_м_09.md`

## Что добавлено

1. Новый marker `prod_mutation` в Python autotests:
- `tools/autotests/test_prod_mutation_api.py`

2. Сценарии mutation:
- проверка бота магазина:
  - обновление shop token/chat,
  - `bot-connect`,
  - `bot-status`,
  - восстановление исходных shop-настроек;
- товарный цикл:
  - создать товар,
  - удалить товар;
- Telegram link цикл:
  - unlink,
  - generate token,
  - webhook `/start <token>`,
  - проверка что профиль снова linked.

3. Защитный флаг:
- `AUTO_TEST_ALLOW_MUTATION=1` обязателен для запуска mutation-тестов.

4. Обновлены:
- `tools/run_prod_smoke.py` (`--include-mutation`)
- `tools/autotests/.env.prod.example` (новые AUTO_TEST_* переменные)
- `scripts/dev-shortcuts.ps1` (новая команда `autotest-prod-mutation`)
- `md/AUTOTEST_PROD_RUNBOOK.md`
- `md/TEST_REPORT_TEMPLATE.md`

## Важно

Тесты предназначены для выделенного тестового аккаунта/магазина на проде.
Изменения делаются локально; без push в `prod` до отдельного подтверждения.

