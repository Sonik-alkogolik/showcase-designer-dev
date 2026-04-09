from __future__ import annotations

import argparse
import json
import random
from pathlib import Path
from time import sleep

from playwright.sync_api import sync_playwright


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="E2E browser flow: login -> shop settings -> save bot token -> reload -> verify state"
    )
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="App base URL")
    parser.add_argument("--shop-id", default="2", help="Target shop id for settings page")
    parser.add_argument("--email", default="test2@gmail.com", help="Login email")
    parser.add_argument("--password", default="1234", help="Login password")
    parser.add_argument("--bot-token", required=True, help="Telegram bot token to set")
    parser.add_argument("--browser", choices=["chromium", "chrome"], default="chrome")
    parser.add_argument("--headless", action="store_true", help="Run headless")
    parser.add_argument("--human", action="store_true", help="Simulate human typing/pause")
    parser.add_argument("--slow-ms", type=int, default=140, help="Slow motion delay")
    parser.add_argument("--screenshot", default="tools/e2e_shop_settings_token.png")
    parser.add_argument("--report-json", default="tools/e2e_shop_settings_token_report.json")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    fallback_credentials = [
        ("test2@gmail.com", "1234"),
        ("test@example.com", "password"),
    ]

    with sync_playwright() as p:
        launch_args = {"headless": args.headless}
        if args.human:
            launch_args["slow_mo"] = args.slow_ms
        if args.browser == "chrome":
            launch_args["channel"] = "chrome"
        browser = p.chromium.launch(**launch_args)
        page = browser.new_page()

        def human_pause(a: float = 0.15, b: float = 0.45) -> None:
            if args.human:
                sleep(random.uniform(a, b))

        def human_type(selector: str, value: str) -> None:
            page.click(selector)
            page.keyboard.press("Control+a")
            page.keyboard.press("Backspace")
            for ch in value:
                page.keyboard.type(ch, delay=max(20, args.slow_ms // 2))
            human_pause()

        def first_visible_selector(selectors: list[str]) -> str:
            for selector in selectors:
                if page.locator(selector).count():
                    return selector
            raise RuntimeError(f"Could not find selector from: {selectors}")

        # login
        page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=30000)
        email_selector = first_visible_selector([
            'input[placeholder="you@example.com"]',
            'input[placeholder="Email"]',
            'input[type="email"]',
        ])
        password_selector = first_visible_selector([
            'input[placeholder="Ваш пароль"]',
            'input[placeholder="Пароль"]',
            'input[type="password"]',
        ])
        submit_selector = first_visible_selector([
            'button[type="submit"]',
            'button:has-text("Войти")',
        ])

        credentials = [(args.email, args.password)]
        for item in fallback_credentials:
            if item not in credentials:
                credentials.append(item)

        used_email = ""
        auth_token = ""
        for try_email, try_password in credentials:
            page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=30000)
            human_pause(0.4, 0.8)
            human_type(email_selector, try_email)
            human_type(password_selector, try_password)
            page.click(submit_selector)
            page.wait_for_timeout(1800)
            auth_token = page.evaluate("() => localStorage.getItem('auth_token')") or ""
            if auth_token:
                used_email = try_email
                break

        token_present = bool(auth_token)

        # settings page
        settings_url = f"{args.base_url}/shops/{args.shop_id}/settings"
        page.goto(settings_url, wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(1800)

        input_selector = "#bot_token"
        save_selector = 'button[type="submit"]'
        has_input = page.locator(input_selector).count() > 0

        if has_input:
            human_type(input_selector, args.bot_token)
            page.click(save_selector)
            page.wait_for_timeout(2200)

        success_text = ""
        if page.locator(".alert-success").count() > 0:
            success_text = page.locator(".alert-success").inner_text().strip()

        error_text = ""
        if page.locator(".alert-error").count() > 0:
            error_text = page.locator(".alert-error").inner_text().strip()

        field_error = ""
        if page.locator(".error-message").count() > 0:
            field_error = page.locator(".error-message").first.inner_text().strip()

        has_token_label_after_save = page.locator("text=Токен сохранен в магазине").count() > 0

        # reload and verify persistent UI state
        page.reload(wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(1800)
        has_token_label_after_reload = page.locator("text=Токен сохранен в магазине").count() > 0

        # verify API flag with same auth token
        api_has_bot_token = None
        api_status = None
        api_error = ""
        if auth_token:
            api_resp = page.request.get(
                f"{args.base_url}/api/shops/{args.shop_id}",
                headers={"Authorization": f"Bearer {auth_token}"},
            )
            api_status = api_resp.status
            if api_resp.ok:
                payload = api_resp.json()
                api_has_bot_token = bool(((payload or {}).get("shop") or {}).get("has_bot_token"))
            else:
                api_error = api_resp.text()

        screenshot_path = Path(args.screenshot)
        screenshot_path.parent.mkdir(parents=True, exist_ok=True)
        page.screenshot(path=str(screenshot_path), full_page=True)

        report = {
            "base_url": args.base_url,
            "shop_id": args.shop_id,
            "used_email": used_email or args.email,
            "token_present": token_present,
            "has_bot_token_input": has_input,
            "success_text": success_text,
            "error_text": error_text,
            "field_error": field_error,
            "has_token_label_after_save": has_token_label_after_save,
            "has_token_label_after_reload": has_token_label_after_reload,
            "api_status": api_status,
            "api_has_bot_token": api_has_bot_token,
            "api_error": api_error,
            "settings_url": settings_url,
            "final_url": page.url,
            "screenshot": str(screenshot_path),
        }

        report_path = Path(args.report_json)
        report_path.parent.mkdir(parents=True, exist_ok=True)
        report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

        browser.close()

    print(f"EMAIL: {report['used_email']}")
    print(f"TOKEN_PRESENT: {report['token_present']}")
    print(f"HAS_INPUT: {report['has_bot_token_input']}")
    print(f"SUCCESS_TEXT: {report['success_text']}")
    print(f"ERROR_TEXT: {report['error_text']}")
    print(f"FIELD_ERROR: {report['field_error']}")
    print(f"LABEL_AFTER_SAVE: {report['has_token_label_after_save']}")
    print(f"LABEL_AFTER_RELOAD: {report['has_token_label_after_reload']}")
    print(f"API_STATUS: {report['api_status']}")
    print(f"API_HAS_BOT_TOKEN: {report['api_has_bot_token']}")
    print(f"FINAL_URL: {report['final_url']}")
    print(f"REPORT_JSON: {report_path}")
    print(f"SCREENSHOT: {screenshot_path}")

    ok = (
        report["token_present"]
        and report["has_bot_token_input"]
        and report["api_status"] == 200
        and report["api_has_bot_token"] is True
        and not report["error_text"]
        and not report["field_error"]
    )
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())

