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
        description="E2E browser flow: login + create shop + add product + verify in products list"
    )
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="Backend app URL")
    parser.add_argument("--email", default="test2@gmail.com", help="Login email")
    parser.add_argument("--password", default="1234", help="Login password")
    parser.add_argument("--delivery-name", default="Курьер", help="Delivery method name")
    parser.add_argument("--delivery-price", default="180", help="Delivery price")
    parser.add_argument("--chat-id", default="954773719", help="Notification chat id")
    parser.add_argument("--product-name-prefix", default="UI E2E Product", help="Product name prefix")
    parser.add_argument("--product-price", default="990", help="Product price")
    parser.add_argument("--product-description", default="Автотестовый товар", help="Product description")
    parser.add_argument("--product-category", default="E2E", help="Product category")
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
        "--keep-open",
        action="store_true",
        help="Keep browser open after test finishes (for manual follow-up)",
    )
    parser.add_argument(
        "--screenshot",
        default="tools/e2e_create_shop_result.png",
        help="Screenshot output path",
    )
    parser.add_argument(
        "--report-json",
        default="tools/e2e_create_shop_report.json",
        help="JSON report output path",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    shop_name = f"UI E2E Shop {datetime.now().strftime('%Y%m%d-%H%M%S')}"
    product_name = f"{args.product_name_prefix} {datetime.now().strftime('%H%M%S')}"

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

        page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=30000)
        human_pause(0.4, 0.8)
        human_type('input[placeholder="Email"]', args.email)
        human_type('input[placeholder="Пароль"]', args.password)
        page.click('button:has-text("Войти")')
        page.wait_for_timeout(2000)

        token = page.evaluate("() => localStorage.getItem('auth_token')")
        token_present = bool(token)

        page.goto(f"{args.base_url}/", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(2500)

        has_form = page.locator("#name").count() > 0
        limit_info = ""
        if page.locator(".limits-info").count() > 0:
            limit_info = page.locator(".limits-info").inner_text()

        created = False
        error_text = ""
        if has_form:
            human_type("#name", shop_name)
            human_type("#delivery_name", args.delivery_name)
            human_type("#delivery_price", args.delivery_price)
            human_type("#notification_chat_id", args.chat_id)
            page.click('button:has-text("Создать магазин")')
            page.wait_for_timeout(2500)
            created = page.locator("text=Магазин успешно создан!").count() > 0

            if page.locator(".alert-error").count() > 0:
                error_text = page.locator(".alert-error").inner_text().strip()

        page.goto(f"{args.base_url}/shops", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(2000)
        present = page.locator(f"text={shop_name}").count() > 0

        product_created = False
        product_present = False
        product_error_text = ""
        product_url = ""

        if present:
            shop_card = page.locator(".shop-card", has_text=shop_name).first
            if shop_card.count() > 0:
                products_link = shop_card.locator('a:has-text("Товары")').first
                if products_link.count() > 0:
                    product_href = products_link.get_attribute("href") or ""
                    page.goto(f"{args.base_url}{product_href}", wait_until="domcontentloaded", timeout=30000)
                    page.wait_for_timeout(1800)
                    product_url = page.url

                    add_button = page.locator('button:has-text("Добавить товар")').first
                    if add_button.count() > 0:
                        add_button.click()
                        page.wait_for_timeout(500)

                        modal = page.locator(".modal .modal-content").first
                        if modal.count() > 0:
                            # Product form in this modal has no stable ids, so we fill fields by order.
                            modal.locator(".form-group input").nth(0).fill(product_name)
                            modal.locator('.form-group input[type="number"]').first.fill(args.product_price)
                            modal.locator(".form-group textarea").first.fill(args.product_description)
                            modal.locator(".form-group input").nth(2).fill(args.product_category)
                            modal.locator('button[type="submit"]:has-text("Сохранить")').first.click()
                            page.wait_for_timeout(2200)

                            product_created = page.locator(".products-grid .product-card h3", has_text=product_name).count() > 0
                            product_present = product_created

                            if page.locator(".alert-error").count() > 0:
                                product_error_text = page.locator(".alert-error").inner_text().strip()

                            if not product_created:
                                product_present = page.locator(f"text={product_name}").count() > 0

        screenshot_path = Path(args.screenshot)
        screenshot_path.parent.mkdir(parents=True, exist_ok=True)
        page.screenshot(path=str(screenshot_path), full_page=True)

        report = {
            "email": args.email,
            "base_url": args.base_url,
            "shop_name": shop_name,
            "token_present": token_present,
            "has_form": has_form,
            "limit_info": limit_info,
            "created_success_message": created,
            "shop_present_in_list": present,
            "product_name": product_name,
            "product_created": product_created,
            "product_present_in_products_list": product_present,
            "product_url": product_url,
            "product_error_text": product_error_text,
            "error_text": error_text,
            "final_url": page.url,
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

    print(f"EMAIL: {args.email}")
    print(f"BROWSER: {args.browser}")
    print(f"HEADLESS: {args.headless}")
    print(f"HUMAN_MODE: {args.human}")
    print(f"KEEP_OPEN: {args.keep_open}")
    print(f"TOKEN_PRESENT: {token_present}")
    print(f"HAS_FORM: {has_form}")
    print(f"LIMIT_INFO: {limit_info.replace(chr(10), ' | ')}")
    print(f"SHOP_NAME: {shop_name}")
    print(f"CREATED_SUCCESS_MESSAGE: {created}")
    print(f"SHOP_PRESENT_IN_LIST: {present}")
    print(f"PRODUCT_NAME: {product_name}")
    print(f"PRODUCT_CREATED: {product_created}")
    print(f"PRODUCT_PRESENT_IN_PRODUCTS_LIST: {product_present}")
    print(f"PRODUCT_URL: {product_url}")
    print(f"PRODUCT_ERROR_TEXT: {product_error_text}")
    print(f"FINAL_URL: {report['final_url']}")
    print(f"REPORT_JSON: {report_path}")
    print(f"SCREENSHOT: {screenshot_path}")

    return 0 if (token_present and has_form and created and present and product_created and product_present) else 1


if __name__ == "__main__":
    raise SystemExit(main())
