# TEST REPORT TEMPLATE

Дата: YYYY-MM-DD  
Ветка/коммит: `<hash>`  
Окружение: `prod_smoke` / `staging` / `local`

## 1) Автотесты

- Команда:
`python tools/run_prod_smoke.py --env-file tools/autotests/.env.prod`
- Результат: `PASS` / `FAIL`
- Время выполнения: `__ мин __ сек`

### Детали

1. `test_public_root_available` — `PASS/FAIL`
2. `test_login_profile_and_logout` — `PASS/FAIL`
3. `test_public_shop_endpoint` — `PASS/FAIL`

Артефакты:

- `tools/autotest-reports/prod-smoke-junit.xml`
- `tools/autotest-reports/*.json`

## 2) Ручной smoke (если нужен)

1. Логин в UI — `PASS/FAIL`
2. Dashboard открывается — `PASS/FAIL`
3. `/app?shop={id}` открывается — `PASS/FAIL`

## 3) Логи сервера

- `tail -n 120 storage/logs/laravel.log` — `OK / есть ошибки`
- Критичные ошибки: `да/нет`

## 4) Итог

- Готово к релизу: `ДА/НЕТ`
- Блокеры:
  - ...
- Следующие действия:
  1. ...
  2. ...

