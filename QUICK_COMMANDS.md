# Quick Commands

Ниже короткий набор команд для ежедневной работы.  
Запускать из PowerShell в корне проекта.

## Быстрый старт

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
.\scripts\dev-shortcuts.ps1 help
```

## Готовые команды

```powershell
# Открыть проект в VS Code
.\scripts\dev-shortcuts.ps1 open-project

# Открыть приложение в браузере
.\scripts\dev-shortcuts.ps1 open-browser

# Открыть страницу логина
.\scripts\dev-shortcuts.ps1 open-login

# Проверить API логин (по умолчанию test@example.com / password)
.\scripts\dev-shortcuts.ps1 api-login

# Проверить API логин с вашими данными
.\scripts\dev-shortcuts.ps1 api-login -Email "your@email.com" -Password "your_password"

# Проверить подключение к БД
.\scripts\dev-shortcuts.ps1 db-ping

# Открыть MySQL shell (showcase_designer)
.\scripts\dev-shortcuts.ps1 db-shell

# Запустить web UI быстрых команд
.\scripts\dev-shortcuts.ps1 start-ui
```

После запуска `start-ui` откроется страница:
`http://127.0.0.1:8787`

В UI есть блок **HTTP Test Lab**:
1. `Preset: test-cors` — проверка `GET /api/test-cors`
2. `Preset: login` — проверка `POST /api/login`
3. `Preset: profile` — проверка `GET /api/profile` (нужен Bearer token)
4. Можно вручную задать Method/URL/Body/Token и отправить запрос кнопкой `Send Request`.

## Как добавлять свои команды

1. Откройте файл `scripts/dev-shortcuts.ps1`.
2. Добавьте новый `switch`-блок по шаблону:

```powershell
"my-command" {
    # TODO: your command
    break
}
```

3. Добавьте строку с описанием в блок `help`.
