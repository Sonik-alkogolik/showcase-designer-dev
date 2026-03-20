param(
    [Parameter(Position = 0)]
    [string]$Action = "help",

    [string]$Email = "test@example.com",
    [string]$Password = "password"
)

$ProjectRoot = "C:\Users\admin\Desktop\myproject\showcase-designer"
$AppUrl = "http://127.0.0.1:8000"
$LoginUrl = "$AppUrl/login"
$ApiLoginUrl = "$AppUrl/api/login"

$MysqlCli = "C:\OSPanel\modules\MySQL-8.0\bin\mysql.exe"
$DbHost = "127.127.126.26"
$DbPort = "3306"
$DbName = "showcase_designer"
$DbUser = "root"
$UiUrl = "http://127.0.0.1:8787"

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
  db-shell      - open MySQL shell for showcase_designer
  start-ui      - start Python web UI for quick commands

Examples:
  .\scripts\dev-shortcuts.ps1 open-project
  .\scripts\dev-shortcuts.ps1 api-login
  .\scripts\dev-shortcuts.ps1 db-ping
  .\scripts\dev-shortcuts.ps1 api-login -Email "dev@example.com" -Password "password"
  .\scripts\dev-shortcuts.ps1 start-ui
  .\scripts\dev-shortcuts.ps1 db-shell
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

    "db-ping" {
        & $MysqlCli -h $DbHost -P $DbPort -u $DbUser -e "SELECT 1 AS ok;"
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
