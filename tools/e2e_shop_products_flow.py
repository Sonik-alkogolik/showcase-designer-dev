from __future__ import annotations

import argparse
import json
import random
from datetime import datetime
from pathlib import Path
from time import sleep

from playwright.sync_api import sync_playwright


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="E2E UI check for existing shop: add category + add product + verify in list"
    )
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="Backend app URL")
    parser.add_argument("--email", default="test2@gmail.com", help="Login email")
    parser.add_argument("--password", default="1234", help="Login password")
    parser.add_argument("--shop-id", default="2", help="Existing shop id to test")
    parser.add_argument("--category-prefix", default="UI E2E Cat", help="Category name prefix")
    parser.add_argument("--product-prefix", default="UI E2E Product", help="Product name prefix")
    parser.add_argument("--product-price", default="777", help="Product price")
    parser.add_argument("--product-description", default="Проверка ручного UI потока", help="Product description")
    parser.add_argument(
        "--browser",
        choices=["chromium", "chrome"],
        default="chromium",
        help="Browser engine to run Playwright with",
    )
    parser.add_argument("--headless", action="store_true", help="Run browser headless")
    parser.add_argument("--human", action="store_true", help="Simulate real user behavior")
    parser.add_argument("--slow-ms", type=int, default=120, help="Slow motion delay for browser actions")
    parser.add_argument(
        "--screenshot",
        default="tools/e2e_shop_products_flow.png",
        help="Screenshot output path",
    )
    parser.add_argument(
        "--report-json",
        default="tools/e2e_shop_products_flow_report.json",
        help="JSON report output path",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    stamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    category_name = f"{args.category_prefix} {stamp}"
    product_name = f"{args.product_prefix} {stamp}"
    target_url = f"{args.base_url}/shops/{args.shop_id}/products"
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

        dialog_messages: list[str] = []
        captured_requests: list[dict[str, object]] = []

        def on_dialog(dialog) -> None:  # type: ignore[no-untyped-def]
            dialog_messages.append(f"{dialog.type}: {dialog.message}")
            dialog.accept()

        page.on("dialog", on_dialog)

        def on_response(resp) -> None:  # type: ignore[no-untyped-def]
            url = resp.url
            if f"/api/shops/{args.shop_id}/categories" in url or f"/api/shops/{args.shop_id}/products" in url:
                body_preview = ""
                try:
                    body_preview = resp.text()[:350]
                except Exception:
                    body_preview = ""
                captured_requests.append(
                    {
                        "url": url,
                        "status": resp.status,
                        "method": resp.request.method,
                        "body_preview": body_preview,
                    }
                )

        page.on("response", on_response)

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
            raise RuntimeError(f"Could not find any matching selector from: {selectors}")

        page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=30000)
        human_pause(0.4, 0.8)
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
        token = ""
        for try_email, try_password in credentials:
            page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=30000)
            human_pause(0.4, 0.8)
            human_type(email_selector, try_email)
            human_type(password_selector, try_password)
            page.click(submit_selector)
            page.wait_for_timeout(1800)
            token = page.evaluate("() => localStorage.getItem('auth_token')") or ""
            if token:
                break
        token_present = bool(token)

        page.goto(target_url, wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(2200)

        page_url = page.url
        redirected_to_login = "/login" in page_url
        has_products_header = page.locator("h1", has_text="Товары магазина").count() > 0
        limits_info = ""
        if page.locator(".limits-info").count() > 0:
            limits_info = page.locator(".limits-info").inner_text().strip()

        category_created = False
        product_created = False
        product_has_category = False
        product_error = ""

        if token_present and not redirected_to_login and has_products_header:
            categories_btn = page.locator('button:has-text("Категории")').first
            if categories_btn.count() > 0:
                categories_btn.click()
                page.wait_for_timeout(500)
                category_input = page.locator('.categories-modal input[placeholder="Название категории"]').first
                if category_input.count() > 0:
                    category_input.fill(category_name)
                    page.locator('.categories-modal .category-form .btn-primary:has-text("Добавить")').first.click()
                    page.wait_for_timeout(1200)
                    category_created = page.locator(".categories-list .category-name", has_text=category_name).count() > 0
                page.locator('.categories-modal button:has-text("Закрыть")').first.click()
                page.wait_for_timeout(500)

            add_product_btn = page.locator('button:has-text("Добавить товар")').first
            if add_product_btn.count() > 0:
                add_product_btn.click()
                page.wait_for_timeout(500)
                modal = page.locator(".modal .modal-content", has_text="Создать товар").first
                if modal.count() > 0:
                    modal.locator(".form-group input").nth(0).fill(product_name)
                    modal.locator('.form-group input[type="number"]').first.fill(args.product_price)
                    modal.locator(".form-group textarea").first.fill(args.product_description)
                    modal.locator(".form-group input").nth(2).fill(category_name)
                    modal.locator('button[type="submit"]:has-text("Сохранить")').first.click()
                    page.wait_for_timeout(2200)
                    product_created = page.locator(".products-grid .product-card h3", has_text=product_name).count() > 0
                    product_has_category = (
                        page.locator(".products-grid .product-card", has_text=product_name)
                        .locator(".category", has_text=category_name)
                        .count()
                        > 0
                    )
                    if page.locator(".alert-error").count() > 0:
                        product_error = page.locator(".alert-error").inner_text().strip()

        screenshot_path = Path(args.screenshot)
        screenshot_path.parent.mkdir(parents=True, exist_ok=True)
        page.screenshot(path=str(screenshot_path), full_page=True)

        report = {
            "base_url": args.base_url,
            "shop_id": args.shop_id,
            "target_url": target_url,
            "final_url": page.url,
            "token_present": token_present,
            "redirected_to_login": redirected_to_login,
            "has_products_header": has_products_header,
            "limits_info": limits_info,
            "category_name": category_name,
            "category_created": category_created,
            "product_name": product_name,
            "product_created": product_created,
            "product_has_category": product_has_category,
            "product_error": product_error,
            "dialogs": dialog_messages,
            "captured_requests": captured_requests,
            "screenshot": str(screenshot_path),
        }

        report_path = Path(args.report_json)
        report_path.parent.mkdir(parents=True, exist_ok=True)
        report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
        browser.close()

    print(f"TARGET_URL: {target_url}")
    print(f"FINAL_URL: {report['final_url']}")
    print(f"TOKEN_PRESENT: {token_present}")
    print(f"REDIRECTED_TO_LOGIN: {redirected_to_login}")
    print(f"HAS_PRODUCTS_HEADER: {has_products_header}")
    print(f"LIMITS_INFO: {limits_info.replace(chr(10), ' | ')}")
    print(f"CATEGORY_NAME: {category_name}")
    print(f"CATEGORY_CREATED: {category_created}")
    print(f"PRODUCT_NAME: {product_name}")
    print(f"PRODUCT_CREATED: {product_created}")
    print(f"PRODUCT_HAS_CATEGORY: {product_has_category}")
    print(f"PRODUCT_ERROR: {product_error}")
    print(f"DIALOGS: {len(dialog_messages)}")
    for idx, msg in enumerate(dialog_messages, start=1):
        print(f"DIALOG_{idx}: {msg}")
    print(f"CAPTURED_REQUESTS: {len(captured_requests)}")
    for idx, item in enumerate(captured_requests, start=1):
        print(f"REQ_{idx}: [{item['method']}] {item['status']} {item['url']}")
    print(f"REPORT_JSON: {report_path}")
    print(f"SCREENSHOT: {screenshot_path}")

    return 0 if (token_present and has_products_header and category_created and product_created) else 1


if __name__ == "__main__":
    raise SystemExit(main())
