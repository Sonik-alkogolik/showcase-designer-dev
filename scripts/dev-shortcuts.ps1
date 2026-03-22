param(
    [Parameter(Position = 0)]
    [string]$Action = "help",

    [string]$Email = "test@example.com",
    [string]$Password = "password",
    [string]$PublicUrl = "",
    [string]$ShopId = "2",
    [string]$ChatId = ""
)

$ProjectRoot = "C:\Users\admin\Desktop\myproject\showcase-designer"
$AppUrl = "http://127.0.0.1:8000"
$LoginUrl = "$AppUrl/login"
$ApiLoginUrl = "$AppUrl/api/login"

$MysqlCli = "C:\OSPanel\modules\MySQL-8.0\bin\mysql.exe"
$PhpCli = "C:\OSPanel\modules\PHP-8.2\PHP\php.exe"
$OsPanelCli = "C:\OSPanel\bin\osp.bat"
$DbHost = "127.127.126.26"
$DbPort = "3306"
$DbName = "showcase_designer"
$DbUser = "root"
$UiUrl = "http://127.0.0.1:8787"
$PhpTmpDir = Join-Path $ProjectRoot "storage\framework\testing\tmp"
$TunnelOut = Join-Path $ProjectRoot "tools\tmp-lt.out"
$TunnelErr = Join-Path $ProjectRoot "tools\tmp-lt.err"

switch ($Action) {
    "help" {
        @"
Available actions:
  help          - show this help
  open-project  - open project in VS Code
  open-browser  - open app in browser
  open-login    - open login page in browser
  api-login     - test API login request
  db-ping       - check DB connection (SELECT 1)
  db-up         - start MySQL-8.0 via OSPanel CLI and run db-ping
  db-shell      - open MySQL shell for showcase_designer
  serve         - run Laravel dev server on 127.0.0.1:8000 (foreground)
  tunnel-up     - start localtunnel (port 8000) in background and print public URL
  telegram-pin-current-tunnel - pin Telegram dev bot to URL from tools/tmp-lt.out
  test-shop-create - run shop creation feature test
  e2e-auth-login - run browser E2E: login check (headless)
  e2e-auth-login-chrome - run browser E2E: login check (visible Chrome)
  e2e-auth-login-real-user - run browser E2E: login check (human-like visible Chrome)
  e2e-create-shop - run browser E2E: login + create shop + add product + verify (headless)
  e2e-create-shop-chrome - run browser E2E: login + create shop + add product + verify (visible Chrome)
  e2e-create-shop-real-user - run browser E2E: create shop + add product (human-like visible Chrome)
  e2e-full-real-user - run full browser suite: login + create shop + add product (human-like visible Chrome)
  telegram-pin-dev - pin Telegram dev bot to current APP_URL (set env + webhook + status)
  telegram-send-webapp-test - send test message with WebApp button to specific chat_id
  start-ui      - start Python web UI for quick commands

Examples:
  .\scripts\dev-shortcuts.ps1 open-project
  .\scripts\dev-shortcuts.ps1 api-login
  .\scripts\dev-shortcuts.ps1 db-ping
  .\scripts\dev-shortcuts.ps1 db-up
  .\scripts\dev-shortcuts.ps1 serve
  .\scripts\dev-shortcuts.ps1 tunnel-up
  .\scripts\dev-shortcuts.ps1 telegram-pin-current-tunnel -ShopId "2"
  .\scripts\dev-shortcuts.ps1 api-login -Email "dev@example.com" -Password "password"
  .\scripts\dev-shortcuts.ps1 start-ui
  .\scripts\dev-shortcuts.ps1 db-shell
  .\scripts\dev-shortcuts.ps1 test-shop-create
  .\scripts\dev-shortcuts.ps1 e2e-auth-login
  .\scripts\dev-shortcuts.ps1 e2e-auth-login-chrome
  .\scripts\dev-shortcuts.ps1 e2e-auth-login-real-user
  .\scripts\dev-shortcuts.ps1 e2e-create-shop
  .\scripts\dev-shortcuts.ps1 e2e-create-shop-chrome
  .\scripts\dev-shortcuts.ps1 e2e-create-shop-real-user
  .\scripts\dev-shortcuts.ps1 e2e-full-real-user
  .\scripts\dev-shortcuts.ps1 telegram-pin-dev
  .\scripts\dev-shortcuts.ps1 telegram-pin-dev -PublicUrl "https://showcase-dev-20260321.loca.lt" -ShopId "2"
  .\scripts\dev-shortcuts.ps1 telegram-send-webapp-test -ChatId "123456789" -ShopId "2"
"@
        break
    }

    "open-project" {
        Set-Location $ProjectRoot
        code .
        break
    }

    "open-browser" {
        Start-Process $AppUrl
        break
    }

    "open-login" {
        Start-Process $LoginUrl
        break
    }

    "api-login" {
        $body = @{
            email = $Email
            password = $Password
        } | ConvertTo-Json

        try {
            $response = Invoke-WebRequest -Uri $ApiLoginUrl -Method Post -ContentType "application/json" -Body $body
            "HTTP $($response.StatusCode)"
            $response.Content
        }
        catch {
            if ($_.Exception.Response) {
                $status = [int]$_.Exception.Response.StatusCode
                "HTTP $status"
            } else {
                $_ | Out-String
            }
        }
        break
    }

    "db-shell" {
        & $MysqlCli -h $DbHost -P $DbPort -u $DbUser $DbName
        break
    }

    "db-up" {
        if (-not (Test-Path $OsPanelCli)) {
            Write-Host "OSPanel CLI not found: $OsPanelCli"
            exit 1
        }

        cmd.exe /c "`"$OsPanelCli`" on MySQL-8.0"
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Failed to start MySQL-8.0 via OSPanel. Is OSPanel running?"
            exit $LASTEXITCODE
        }

        & $MysqlCli -h $DbHost -P $DbPort -u $DbUser -e "SELECT 1 AS ok;"
        break
    }

    "db-ping" {
        & $MysqlCli -h $DbHost -P $DbPort -u $DbUser -e "SELECT 1 AS ok;"
        break
    }

    "serve" {
        Set-Location $ProjectRoot
        & $PhpCli artisan serve --host=127.0.0.1 --port=8000
        break
    }

    "tunnel-up" {
        Set-Location $ProjectRoot

        if (Test-Path $TunnelOut) { Remove-Item $TunnelOut -Force }
        if (Test-Path $TunnelErr) { Remove-Item $TunnelErr -Force }

        Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "cd /d $ProjectRoot && npx.cmd localtunnel --port 8000 > tools\tmp-lt.out 2> tools\tmp-lt.err"
        Start-Sleep -Seconds 6

        if (-not (Test-Path $TunnelOut)) {
            Write-Host "Tunnel log not found: $TunnelOut"
            exit 1
        }

        $urlLine = Get-Content $TunnelOut | Select-String -Pattern "your url is:"
        if (-not $urlLine) {
            Write-Host "Tunnel started, but URL is not ready yet. Check:"
            Write-Host $TunnelOut
            if (Test-Path $TunnelErr) {
                Write-Host ""
                Write-Host "Tunnel stderr:"
                Get-Content $TunnelErr | Select-Object -Last 20
            }
            break
        }

        $lastLine = ($urlLine | Select-Object -Last 1).ToString()
        $urlMatch = [regex]::Match($lastLine, "https://\S+")
        $url = $urlMatch.Value.Trim()
        if (-not $url) {
            Write-Host "Could not parse tunnel URL from line:"
            Write-Host $lastLine
            exit 1
        }
        Write-Host "Tunnel URL:"
        Write-Host $url
        Write-Host ""
        Write-Host "Tip: run telegram pin:"
        Write-Host ".\scripts\dev-shortcuts.ps1 telegram-pin-dev -PublicUrl `"$url`" -ShopId `"$ShopId`""
        break
    }

    "test-shop-create" {
        New-Item -ItemType Directory -Force -Path $PhpTmpDir | Out-Null
        & $PhpCli -d "sys_temp_dir=$PhpTmpDir" artisan test tests\Feature\Shop\ShopCreationTest.php
        break
    }

    "e2e-auth-login" {
        & python tools/e2e_auth_login.py --headless
        break
    }

    "e2e-auth-login-chrome" {
        & python tools/e2e_auth_login.py --browser chrome
        break
    }

    "e2e-auth-login-real-user" {
        & python tools/e2e_auth_login.py --browser chrome --human --slow-ms 140
        break
    }

    "e2e-create-shop" {
        & python tools/e2e_create_shop.py --headless
        break
    }

    "e2e-create-shop-chrome" {
        & python tools/e2e_create_shop.py --browser chrome
        break
    }

    "e2e-create-shop-real-user" {
        & python tools/e2e_create_shop.py --browser chrome --human --slow-ms 140
        break
    }

    "e2e-full-real-user" {
        & python tools/e2e_auth_login.py --browser chrome --human --slow-ms 140
        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
        & python tools/e2e_create_shop.py --browser chrome --human --slow-ms 140 --keep-open
        break
    }

    "telegram-pin-dev" {
        Set-Location $ProjectRoot

        $envPath = Join-Path $ProjectRoot ".env"
        if (-not (Test-Path $envPath)) {
            Write-Host "Missing .env at $envPath"
            exit 1
        }

        $envText = Get-Content $envPath -Raw

        if (-not $PublicUrl) {
            if ($envText -match "(?m)^APP_URL=(.+)$") {
                $PublicUrl = $Matches[1].Trim()
            }
        }

        if (-not $PublicUrl) {
            Write-Host "Public URL is empty. Pass -PublicUrl or set APP_URL in .env"
            exit 1
        }

        $PublicUrl = $PublicUrl.Trim().TrimEnd('/')
        $frontendUrl = $PublicUrl
        $webhookUrl = "$PublicUrl/api/telegram/webhook"
        $menuWebAppUrl = "$PublicUrl/app?shop=$ShopId"

        $botToken = ""
        if ($envText -match "(?m)^TELEGRAM_BOT_TOKEN=(.+)$") {
            $botToken = $Matches[1].Trim()
        }
        if (-not $botToken) {
            Write-Host "TELEGRAM_BOT_TOKEN is empty in .env"
            exit 1
        }

        $envText = [regex]::Replace($envText, "(?m)^APP_URL=.*$", "APP_URL=$PublicUrl")
        $envText = [regex]::Replace($envText, "(?m)^FRONTEND_URL=.*$", "FRONTEND_URL=$frontendUrl")
        $envText = [regex]::Replace($envText, "(?m)^TELEGRAM_WEBHOOK_URL=.*$", "TELEGRAM_WEBHOOK_URL=$webhookUrl")
        Set-Content -Path $envPath -Value $envText -Encoding UTF8

        & $PhpCli artisan config:clear | Out-Host

        $setWebhook = Invoke-RestMethod -Method Get -Uri "https://api.telegram.org/bot$botToken/setWebhook?url=$webhookUrl"
        $webhookInfo = Invoke-RestMethod -Method Get -Uri "https://api.telegram.org/bot$botToken/getWebhookInfo"
        $menuBody = @{
            menu_button = @{
                type = "web_app"
                text = "Open Shop"
                web_app = @{
                    url = $menuWebAppUrl
                }
            }
        } | ConvertTo-Json -Depth 6
        $setMenu = Invoke-RestMethod -Method Post -Uri "https://api.telegram.org/bot$botToken/setChatMenuButton" -ContentType "application/json" -Body $menuBody

        Write-Host "Pinned Telegram DEV:"
        Write-Host "APP_URL=$PublicUrl"
        Write-Host "FRONTEND_URL=$frontendUrl"
        Write-Host "TELEGRAM_WEBHOOK_URL=$webhookUrl"
        Write-Host ""
        Write-Host "setWebhook:"
        $setWebhook | ConvertTo-Json -Depth 10
        Write-Host ""
        Write-Host "setChatMenuButton:"
        $setMenu | ConvertTo-Json -Depth 10
        Write-Host ""
        Write-Host "getWebhookInfo:"
        $webhookInfo | ConvertTo-Json -Depth 10
        break
    }

    "telegram-pin-current-tunnel" {
        Set-Location $ProjectRoot

        if (-not (Test-Path $TunnelOut)) {
            Write-Host "Tunnel output not found: $TunnelOut"
            Write-Host "Run: .\scripts\dev-shortcuts.ps1 tunnel-up"
            exit 1
        }

        $urlLine = Get-Content $TunnelOut | Select-String -Pattern "your url is:" | Select-Object -Last 1
        if (-not $urlLine) {
            Write-Host "Could not parse tunnel URL from: $TunnelOut"
            exit 1
        }

        $urlMatch = [regex]::Match($urlLine.ToString(), "https://\S+")
        $PublicUrl = $urlMatch.Value.Trim()
        if (-not $PublicUrl) {
            Write-Host "Could not parse HTTPS URL from: $TunnelOut"
            exit 1
        }
        Write-Host "Using tunnel URL: $PublicUrl"

        $envPath = Join-Path $ProjectRoot ".env"
        if (-not (Test-Path $envPath)) {
            Write-Host "Missing .env at $envPath"
            exit 1
        }

        $envText = Get-Content $envPath -Raw
        $frontendUrl = $PublicUrl.TrimEnd('/')
        $webhookUrl = "$frontendUrl/api/telegram/webhook"
        $menuWebAppUrl = "$frontendUrl/app?shop=$ShopId"

        $botToken = ""
        if ($envText -match "(?m)^TELEGRAM_BOT_TOKEN=(.+)$") {
            $botToken = $Matches[1].Trim()
        }
        if (-not $botToken) {
            Write-Host "TELEGRAM_BOT_TOKEN is empty in .env"
            exit 1
        }

        $envText = [regex]::Replace($envText, "(?m)^APP_URL=.*$", "APP_URL=$frontendUrl")
        $envText = [regex]::Replace($envText, "(?m)^FRONTEND_URL=.*$", "FRONTEND_URL=$frontendUrl")
        $envText = [regex]::Replace($envText, "(?m)^TELEGRAM_WEBHOOK_URL=.*$", "TELEGRAM_WEBHOOK_URL=$webhookUrl")
        Set-Content -Path $envPath -Value $envText -Encoding UTF8

        & $PhpCli artisan config:clear | Out-Host

        $setWebhook = Invoke-RestMethod -Method Get -Uri "https://api.telegram.org/bot$botToken/setWebhook?url=$webhookUrl"
        $menuBody = @{
            menu_button = @{
                type = "web_app"
                text = "Open Shop"
                web_app = @{
                    url = $menuWebAppUrl
                }
            }
        } | ConvertTo-Json -Depth 6
        $setMenu = Invoke-RestMethod -Method Post -Uri "https://api.telegram.org/bot$botToken/setChatMenuButton" -ContentType "application/json" -Body $menuBody

        Write-Host "Pinned Telegram DEV to current tunnel:"
        Write-Host "APP_URL=$frontendUrl"
        Write-Host "TELEGRAM_WEBHOOK_URL=$webhookUrl"
        Write-Host ""
        Write-Host "setWebhook:"
        $setWebhook | ConvertTo-Json -Depth 10
        Write-Host ""
        Write-Host "setChatMenuButton:"
        $setMenu | ConvertTo-Json -Depth 10
        break
    }

    "telegram-send-webapp-test" {
        Set-Location $ProjectRoot

        if (-not $ChatId) {
            Write-Host "ChatId is empty. Pass -ChatId \"123456789\""
            exit 1
        }

        $envPath = Join-Path $ProjectRoot ".env"
        if (-not (Test-Path $envPath)) {
            Write-Host "Missing .env at $envPath"
            exit 1
        }

        $envText = Get-Content $envPath -Raw
        $botToken = ""
        if ($envText -match "(?m)^TELEGRAM_BOT_TOKEN=(.+)$") {
            $botToken = $Matches[1].Trim()
        }
        if (-not $botToken) {
            Write-Host "TELEGRAM_BOT_TOKEN is empty in .env"
            exit 1
        }

        $baseUrl = $PublicUrl
        if (-not $baseUrl) {
            if ($envText -match "(?m)^APP_URL=(.+)$") {
                $baseUrl = $Matches[1].Trim()
            }
        }
        if (-not $baseUrl) {
            Write-Host "APP_URL is empty in .env and -PublicUrl not passed"
            exit 1
        }

        $baseUrl = $baseUrl.Trim().TrimEnd('/')
        $webAppUrl = "$baseUrl/app?shop=$ShopId"

        $payload = @{
            chat_id = $ChatId
            text = "Тест открытия магазина. Нажмите кнопку ниже."
            reply_markup = @{
                inline_keyboard = @(
                    @(
                        @{
                            text = "Открыть магазин"
                            web_app = @{
                                url = $webAppUrl
                            }
                        }
                    )
                )
            }
        } | ConvertTo-Json -Depth 10

        $resp = Invoke-RestMethod -Method Post -Uri "https://api.telegram.org/bot$botToken/sendMessage" -ContentType "application/json" -Body $payload
        Write-Host "sendMessage:"
        $resp | ConvertTo-Json -Depth 10
        Write-Host ""
        Write-Host "WebApp URL:"
        Write-Host $webAppUrl
        break
    }

    "start-ui" {
        Set-Location $ProjectRoot
        Start-Process python -ArgumentList "tools/dev_ui.py"
        Start-Process $UiUrl
        break
    }

    default {
        Write-Host "Unknown action: $Action"
        Write-Host "Run: .\scripts\dev-shortcuts.ps1 help"
        exit 1
    }
}
