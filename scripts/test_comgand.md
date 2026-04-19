# Test Commands (управление через Side-bar)

Главный способ работы: запускать UI раннер и управлять тестами кнопками в левом side-bar.

## 1) Запуск UI раннера (основной способ)

```powershell
# Переход в корень проекта
cd C:\Users\admin\Desktop\myproject\showcase-designer

# Запуск UI раннера тестов (рекомендуется)
python scripts/test_runner_ui.py
```

```powershell
# Альтернативный запуск через bat-файл (если так удобнее)
cd C:\Users\admin\Desktop\myproject\showcase-designer
scripts\start-test-runner-ui.bat
```

## 2) Что делать внутри UI (без ручного ввода длинных команд)

- В левом side-bar выбираем общий тест или конкретный шаг full-flow.
- Нажимаем кнопку запуска выбранного теста.
- Для ручных шагов используем кнопки:
  - `Открыть браузер`
  - `Закрыть браузер`
- Все шаги полного сценария разнесены по отдельным кнопкам в блоке `Шаги Full E2E`.

## 3) Ручные команды (только если нужно запустить без UI)

```powershell
# Полный прогон real-user сценария
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human
```

```powershell
# Шаг 1: только регистрация
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after register
```

```powershell
# Шаг 2: до выбора тарифа включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after subscribe
```

```powershell
# Шаг 3: до привязки Telegram включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after telegram
```

```powershell
# Шаг 4: до создания магазина включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after shop
```

```powershell
# Шаг 5: до подключения бота включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after connect_bot
```

```powershell
# Шаг 6: до ручного добавления товара включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after add_product
```

```powershell
# Шаг 7: до импорта товара включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after import
```

```powershell
# Шаг 8: полный сценарий до удаления аккаунта включительно
cd C:\Users\admin\Desktop\myproject\showcase-designer
python tools/e2e_prod_real_user_full.py --base-url https://e-tgo.ru --browser chrome --chat-id 954773719 --bot-token <BOT_TOKEN> --plan business --human --stop-after delete
```

## 4) Полезные флаги

```powershell
# Не удалять пользователя в конце (для ручной проверки в админке)
--skip-delete

# Оставить браузер открытым до Enter в консоли
--keep-open

# Ручной Telegram шаг (скрипт ставит паузу и ждет Enter)
--manual-telegram-link

# Headless режим (без видимого окна браузера)
--headless
```

## 5) Проверка, что скрипты корректны

```powershell
# Проверка синтаксиса UI раннера
cd C:\Users\admin\Desktop\myproject\showcase-designer
python -m py_compile scripts/test_runner_ui.py
```

```powershell
# Проверка синтаксиса full e2e скрипта
cd C:\Users\admin\Desktop\myproject\showcase-designer
python -m py_compile tools/e2e_prod_real_user_full.py
```

---

Важно: в обычной работе используем именно UI и кнопки в side-bar. Ручные CLI-команды нужны как fallback.
