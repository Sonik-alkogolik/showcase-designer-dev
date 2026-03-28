# DEV Environment Runbook (Telegram WebApp)

Этот файл — короткая инструкция, как поднять локальное окружение и увидеть товары в Telegram WebApp.

## Shortcut-вариант (через dev-shortcuts)

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
.\scripts\dev-shortcuts.ps1 db-up
.\scripts\dev-shortcuts.ps1 serve
# в новом окне:
.\scripts\dev-shortcuts.ps1 tunnel-up
# затем:
.\scripts\dev-shortcuts.ps1 telegram-pin-current-tunnel -ShopId "2"
# smoke checkout/payment (в новом окне):
.\scripts\dev-shortcuts.ps1 smoke-checkout-payment -ShopId "2"
```

## 1) Откройте проект

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
```

## 2) Поднимите MySQL (OSPanel)

```powershell
cmd.exe /c "C:\OSPanel\bin\osp.bat on MySQL-8.0"
```

Проверка:

```powershell
.\scripts\dev-shortcuts.ps1 db-ping
```

## 3) Поднимите Laravel backend

```powershell
& 'C:\OSPanel\modules\PHP-8.2\PHP\php.exe' artisan serve --host=127.0.0.1 --port=8000
```

Держите это окно открытым.

## 4) Соберите фронтенд и скопируйте в `public`

В новом окне PowerShell:

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer\client
npm run build
Set-Location ..
Copy-Item .\client\dist\* .\public\ -Recurse -Force
```

## 5) Поднимите public tunnel

В отдельном окне PowerShell:

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
npx.cmd localtunnel --port 8000
```

Скопируйте выданный URL вида `https://xxxx.loca.lt` и не закрывайте это окно.

## 6) Привяжите URL к Telegram боту

В новом окне:

```powershell
Set-Location C:\Users\admin\Desktop\myproject\showcase-designer
.\scripts\dev-shortcuts.ps1 telegram-pin-dev -PublicUrl "https://xxxx.loca.lt" -ShopId "2"
```

## 7) Проверка в Telegram

1. Откройте бота и кнопку WebApp.
2. Если появилась страница `Tunnel Password`, введите пароль:
   `151.247.209.157`
3. Убедитесь, что видите каталог товаров.

## 8) Быстрые проверки API (опционально)

```powershell
curl.exe -s "http://127.0.0.1:8000/api/shops/2/products/public"
curl.exe -s "http://127.0.0.1:8000/api/shops/2/public"
.\\scripts\\dev-shortcuts.ps1 smoke-checkout-payment -ShopId "2"
# с проверкой публичного app URL
.\\scripts\\dev-shortcuts.ps1 smoke-checkout-payment -PublicUrl "https://xxxx.loca.lt" -ShopId "2"
```

## Частые проблемы

- `500` на `/api/shops/{id}/products/public`:
  обычно MySQL не поднят. Выполните `osp on MySQL-8.0` и `db-ping`.
- В Telegram старый интерфейс:
  туннель сменился, но бот не перепривязан. Снова выполните `telegram-pin-dev`.
- `Tunnel Unavailable`:
  процесс `localtunnel` завершился. Запустите `npx.cmd localtunnel --port 8000` заново.
