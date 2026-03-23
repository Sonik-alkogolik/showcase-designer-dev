from __future__ import annotations

import argparse
import json
import random
from pathlib import Path
from time import sleep

from playwright.sync_api import sync_playwright


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="E2E browser auth check for showcase-designer")
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="App base URL")
    parser.add_argument("--email", default="test@example.com", help="Login email")
    parser.add_argument("--password", default="password", help="Login password")
    parser.add_argument(
        "--browser",
        choices=["chromium", "chrome"],
        default="chromium",
        help="Browser engine to run Playwright with",
    )
    parser.add_argument("--headless", action="store_true", help="Run browser in headless mode")
    parser.add_argument("--human", action="store_true", help="Simulate real user behavior")
    parser.add_argument("--slow-ms", type=int, default=120, help="Slow motion delay for browser actions")
    parser.add_argument(
        "--keep-open",
        action="store_true",
        help="Keep browser open after test finishes (for manual follow-up)",
    )
    parser.add_argument(
        "--screenshot",
        default="tools/login_after_auth.png",
        help="Path to save page screenshot after login",
    )
    parser.add_argument(
        "--report-json",
        default="tools/login_auth_report.json",
        help="Path to save JSON report with captured network info",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    captured: list[dict[str, object]] = []

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

        def on_response(resp) -> None:  # type: ignore[no-untyped-def]
            url = resp.url
            if "/api/login" in url or "/api/profile" in url:
                captured.append(
                    {
                        "url": url,
                        "status": resp.status,
                        "headers": resp.headers,
                    }
                )

        page.on("response", on_response)
        page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=30000)
        human_pause(0.4, 0.8)
        human_type('input[placeholder="Email"]', args.email)
        human_type('input[placeholder="Пароль"]', args.password)
        page.click('button:has-text("Войти")')
        page.wait_for_timeout(3000)

        token = page.evaluate("() => localStorage.getItem('auth_token')")
        error_text = ""
        error_locator = page.locator('p[style*="color: red"]')
        if error_locator.count():
            error_text = error_locator.inner_text().strip()

        screenshot_path = Path(args.screenshot)
        screenshot_path.parent.mkdir(parents=True, exist_ok=True)
        page.screenshot(path=str(screenshot_path), full_page=True)

        report = {
            "page_url": page.url,
            "auth_token_present": bool(token),
            "auth_token_prefix": token[:20] if token else "",
            "error_text": error_text,
            "captured_requests": captured,
            "screenshot": str(screenshot_path),
        }

        report_path = Path(args.report_json)
        report_path.parent.mkdir(parents=True, exist_ok=True)
        report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

        if args.keep_open and not args.headless:
            print("KEEP_OPEN: True")
            print("Browser will stay open. Close the Chrome window when you are done.")
            while browser.is_connected():
                sleep(1)
        else:
            browser.close()

    print(f"PAGE_URL: {report['page_url']}")
    print(f"BROWSER: {args.browser}")
    print(f"HEADLESS: {args.headless}")
    print(f"HUMAN_MODE: {args.human}")
    print(f"KEEP_OPEN: {args.keep_open}")
    print(f"AUTH_TOKEN_PRESENT: {report['auth_token_present']}")
    print(f"AUTH_TOKEN_PREFIX: {report['auth_token_prefix']}")
    print(f"ERROR_TEXT: {report['error_text']}")
    print(f"CAPTURED_REQUESTS: {len(captured)}")
    print(f"REPORT_JSON: {report_path}")
    print(f"SCREENSHOT: {screenshot_path}")
    for index, item in enumerate(captured, start=1):
        print(f"[{index}] {item['status']} {item['url']}")

    return 0 if report["auth_token_present"] and not error_text else 1


if __name__ == "__main__":
    raise SystemExit(main())
