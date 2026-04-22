from __future__ import annotations

import argparse
import json
import os
import random
import re
from datetime import datetime
from pathlib import Path
from time import sleep
from urllib.parse import parse_qs, urlparse

from playwright.sync_api import sync_playwright


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Full real-user E2E on production: "
            "register -> telegram link(chat_id) -> create shop/bot -> add product -> delete account"
        )
    )
    parser.add_argument("--base-url", default="https://e-tgo.ru", help="Production base URL")
    parser.add_argument("--browser", choices=["chromium", "chrome"], default="chrome")
    parser.add_argument("--headless", action="store_true", help="Run headless")
    parser.add_argument("--human", action="store_true", default=True, help="Simulate human behavior")
    parser.add_argument("--slow-ms", type=int, default=140, help="Slow motion delay")
    parser.add_argument("--bot-token", default=os.getenv("AUTO_TEST_BOT_TOKEN", ""), help="Telegram bot token")
    parser.add_argument("--chat-id", default=os.getenv("AUTO_TEST_CHAT_ID", ""), help="Telegram chat id")
    parser.add_argument(
        "--telegram-username",
        default=os.getenv("AUTO_TEST_TELEGRAM_USERNAME", "e2e_test_user"),
        help="Telegram username for webhook simulation (without @)",
    )
    parser.add_argument("--shop-name-prefix", default="E2E FullFlow Shop")
    parser.add_argument("--category-prefix", default="E2E FullFlow Cat")
    parser.add_argument("--product-prefix", default="E2E FullFlow Product")
    parser.add_argument(
        "--plan",
        choices=["starter", "business"],
        default="business",
        help="Subscription plan to activate for test user",
    )
    parser.add_argument(
        "--manual-telegram-link",
        action="store_true",
        help=(
            "Pause after generating Telegram link and wait for Enter. "
            "Use this if you want to complete Telegram /start manually."
        ),
    )
    parser.add_argument(
        "--keep-open",
        action="store_true",
        help="Keep browser open at the end until you press Enter in terminal.",
    )
    parser.add_argument(
        "--skip-delete",
        action="store_true",
        help="Do not delete account at the end (keep created user/shop for manual checks).",
    )
    parser.add_argument(
        "--stop-after",
        choices=["register", "subscribe", "telegram", "shop", "connect_bot", "add_product", "import", "delete"],
        default=None,
        help="Stop scenario after a specific step (for step-by-step test runs).",
    )
    parser.add_argument("--report-json", default="tools/e2e_prod_real_user_full_report.json")
    parser.add_argument("--screenshot", default="tools/e2e_prod_real_user_full.png")
    return parser.parse_args()


def first_non_empty(*values: object) -> str:
    for value in values:
        text = str(value or "").strip()
        if text:
            return text
    return ""


def main() -> int:
    args = parse_args()
    if not args.chat_id:
        raise SystemExit("AUTO_TEST_CHAT_ID/--chat-id is required")
    if not args.bot_token:
        raise SystemExit("AUTO_TEST_BOT_TOKEN/--bot-token is required")

    def step_order() -> list[str]:
        return ["register", "subscribe_plan", "telegram_link", "create_shop", "connect_bot", "add_product", "import_product", "delete_account"]

    stop_map = {
        "register": "register",
        "subscribe": "subscribe_plan",
        "telegram": "telegram_link",
        "shop": "create_shop",
        "connect_bot": "connect_bot",
        "add_product": "add_product",
        "import": "import_product",
        "delete": "delete_account",
    }

    def should_stop(current_step_key: str) -> bool:
        if not args.stop_after:
            return False
        target = stop_map[args.stop_after]
        return current_step_key == target

    def required_steps_for_run() -> list[str]:
        if not args.stop_after:
            return step_order()
        target = stop_map[args.stop_after]
        ordered = step_order()
        return ordered[: ordered.index(target) + 1]

    stamp = datetime.now().strftime("%Y%m%d%H%M%S")
    email = f"e2e_fullflow_{stamp}@test.local"
    password = "Test1234!"
    name = f"E2E FullFlow {stamp[-6:]}"
    shop_name = f"{args.shop_name_prefix} {stamp[-6:]}"
    category_name = f"{args.category_prefix} {stamp[-6:]}"
    product_name = f"{args.product_prefix} {stamp[-6:]}"
    import_product_name = f"{args.product_prefix} IMPORT {stamp[-6:]}"

    report: dict[str, object] = {
        "base_url": args.base_url,
        "email": email,
        "password": password,
        "steps": {},
        "network": [],
    }

    with sync_playwright() as p:
        launch_args: dict[str, object] = {"headless": args.headless}
        if args.human:
            launch_args["slow_mo"] = args.slow_ms
        if args.browser == "chrome":
            launch_args["channel"] = "chrome"

        browser = p.chromium.launch(**launch_args)
        page = browser.new_page()
        page.on("dialog", lambda d: d.accept())

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
            raise RuntimeError(f"No matching selector found: {selectors}")

        def api_get_json(path: str) -> tuple[bool, dict[str, object]]:
            auth_token = page.evaluate("() => localStorage.getItem('auth_token')") or ""
            if not auth_token:
                return False, {}
            response = page.request.get(
                f"{args.base_url}{path}",
                headers={"Authorization": f"Bearer {auth_token}"},
            )
            if not response.ok:
                return False, {}
            try:
                return True, response.json()
            except Exception:
                return False, {}

        def capture_response(resp) -> None:  # type: ignore[no-untyped-def]
            url = resp.url
            watched = [
                "/api/register",
                "/api/login",
                "/api/profile",
                "/api/profile/telegram/generate-token",
                "/api/telegram/webhook",
                "/api/subscription/subscribe",
                "/api/shops",
                "/api/shops/",
                "/api/shops/",
                "/api/shops/",
                "/api/shops/",
                "/api/shops/",
                "/api/shops/",
                "/api/shops/",
            ]
            if any(part in url for part in watched):
                body_preview = ""
                try:
                    body_preview = resp.text()[:500]
                except Exception:
                    body_preview = ""
                report["network"].append(
                    {
                        "url": url,
                        "status": resp.status,
                        "method": resp.request.method,
                        "body_preview": body_preview,
                    }
                )

        page.on("response", capture_response)

        # 1) Register
        page.goto(f"{args.base_url}/register", wait_until="domcontentloaded", timeout=30000)
        human_pause(0.4, 0.8)
        human_type('input[placeholder="Ваше имя"]', name)
        human_type('input[placeholder="you@example.com"]', email)
        human_type('input[placeholder="Минимум 8 символов"]', password)
        human_type('input[placeholder="Повторите пароль"]', password)
        page.click('button[type="submit"]:has-text("Зарегистрироваться")')
        page.wait_for_timeout(2500)
        token = page.evaluate("() => localStorage.getItem('auth_token')") or ""
        report["steps"]["register"] = {
            "ok": bool(token),
            "final_url": page.url,
        }
        if should_stop("register"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: register")
            print(f"REGISTER_OK: {report['steps']['register']['ok']}")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["register"]["ok"] else 1

        # 1.1) subscribe plan from UI if needed
        page.goto(f"{args.base_url}/plans", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(2200)

        def ensure_plan_subscription() -> tuple[bool, str, dict[str, object]]:
            diagnostics: dict[str, object] = {}

            ok_plans_before, plans_payload_before = api_get_json("/api/subscription/plans")
            current_plan = ""
            if ok_plans_before:
                current_plan = first_non_empty((plans_payload_before.get("current_subscription") or {}).get("plan"))
            diagnostics["initial_plan"] = current_plan

            if current_plan == args.plan:
                diagnostics["already_active"] = True
                return True, current_plan, diagnostics

            if current_plan and current_plan != args.plan:
                diagnostics["blocked_by_existing_plan"] = True
                diagnostics["blocked_message"] = (
                    "Cannot switch active plan in current API flow. "
                    "A fresh user is required for deterministic e2e plan selection."
                )
                return False, current_plan, diagnostics

            auth_token_local = page.evaluate("() => localStorage.getItem('auth_token')") or ""
            if not auth_token_local:
                diagnostics["error"] = "No auth token in localStorage before subscription step"
                return False, current_plan, diagnostics

            subscribe_payload = {
                "plan": args.plan,
                "auto_renew": False,
                "offer_accepted": True,
                "privacy_accepted": True,
            }
            sub_resp = page.request.post(
                f"{args.base_url}/api/subscription/subscribe",
                headers={
                    "Authorization": f"Bearer {auth_token_local}",
                    "Content-Type": "application/json",
                },
                data=json.dumps(subscribe_payload, ensure_ascii=False),
            )
            diagnostics["subscribe_status"] = sub_resp.status
            diagnostics["subscribe_ok"] = bool(sub_resp.ok)
            try:
                sub_json = sub_resp.json()
                diagnostics["subscribe_response"] = sub_json
            except Exception:
                diagnostics["subscribe_response"] = (sub_resp.text() or "")[:500]

            page.wait_for_timeout(900)
            ok_plans_after, plans_payload_after = api_get_json("/api/subscription/plans")
            if ok_plans_after:
                current_plan = first_non_empty((plans_payload_after.get("current_subscription") or {}).get("plan"))
            diagnostics["final_plan"] = current_plan
            return current_plan == args.plan, current_plan, diagnostics

        plan_ok, current_plan, plan_diagnostics = ensure_plan_subscription()
        report["steps"]["subscribe_plan"] = {
            "ok": plan_ok,
            "final_url": page.url,
            "plan_expected": args.plan,
            "plan_actual": current_plan,
            "diagnostics": plan_diagnostics,
        }
        if should_stop("subscribe_plan"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: subscribe")
            print(f"PLAN_OK: {report['steps']['subscribe_plan']['ok']} ({report['steps']['subscribe_plan'].get('plan_actual', 'n/a')})")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["subscribe_plan"]["ok"] else 1

        # 2) Link Telegram (generate token via UI + webhook simulate)
        page.goto(f"{args.base_url}/profile", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(2000)
        page.click('button:has-text("Подключить Telegram")')
        page.wait_for_timeout(1200)
        bot_link_el = page.locator("a.bot-link").first
        bot_link = bot_link_el.get_attribute("href") if bot_link_el.count() else None
        start_token = ""
        if bot_link:
            parsed = urlparse(bot_link)
            start_token = (parse_qs(parsed.query).get("start") or [""])[0]

        # Fallback: если UI-ссылка не прочиталась, запрашиваем токен напрямую из API.
        if not start_token:
            auth_token_local = page.evaluate("() => localStorage.getItem('auth_token')") or ""
            if auth_token_local:
                fallback_resp = page.request.post(
                    f"{args.base_url}/api/profile/telegram/generate-token",
                    headers={"Authorization": f"Bearer {auth_token_local}"},
                )
                if fallback_resp.ok:
                    try:
                        fallback_payload = fallback_resp.json()
                    except Exception:
                        fallback_payload = {}
                    token_from_api = first_non_empty(fallback_payload.get("token"))
                    link_from_api = first_non_empty(fallback_payload.get("bot_link"))
                    if token_from_api:
                        start_token = token_from_api
                    if link_from_api:
                        bot_link = link_from_api

        webhook_ok = False
        webhook_status = None
        webhook_body_preview = ""
        if start_token and not args.manual_telegram_link:
            update_payload = {
                "update_id": int(datetime.now().timestamp()),
                "message": {
                    "message_id": 1001,
                    "from": {
                        "id": int(args.chat_id),
                        "is_bot": False,
                        "first_name": "E2E",
                        "username": str(args.telegram_username).lstrip("@"),
                    },
                    "chat": {
                        "id": int(args.chat_id),
                        "first_name": "E2E",
                        "username": str(args.telegram_username).lstrip("@"),
                        "type": "private",
                    },
                    "date": int(datetime.now().timestamp()),
                    "text": f"/start {start_token}",
                },
            }
            resp = page.request.post(
                f"{args.base_url}/api/telegram/webhook",
                headers={"Content-Type": "application/json"},
                data=json.dumps(update_payload, ensure_ascii=False),
            )
            webhook_status = resp.status
            try:
                webhook_json = resp.json()
                webhook_body_preview = json.dumps(webhook_json, ensure_ascii=False)[:500]
                if isinstance(webhook_json, dict) and "success" in webhook_json:
                    webhook_ok = bool(webhook_json.get("success"))
                elif isinstance(webhook_json, dict) and "ok" in webhook_json:
                    webhook_ok = bool(webhook_json.get("ok"))
                else:
                    msg = first_non_empty(webhook_json.get("message") if isinstance(webhook_json, dict) else "")
                    webhook_ok = resp.ok and ("успеш" in msg.lower() or "success" in msg.lower())
            except Exception:
                webhook_body_preview = (resp.text() or "")[:500]
                msg = webhook_body_preview.lower()
                webhook_ok = resp.ok and ("успеш" in msg or "success" in msg)

        if start_token and args.manual_telegram_link:
            print("")
            print("=== MANUAL STEP: TELEGRAM LINK ===")
            print("1) Open this link in Telegram and press Start:")
            print(f"   {bot_link}")
            print("2) If needed, send command manually:")
            print(f"   /start {start_token}")
            print("3) Return to terminal and press Enter to continue the scenario.")
            input("Press Enter when Telegram account is linked...")
            print("Continuing scenario...")
            print("")
            webhook_ok = True

        check_btn = page.locator('button:has-text("Проверить привязку")').first
        if check_btn.count() > 0:
            try:
                check_btn.click(timeout=8000, force=True)
            except Exception:
                page.reload(wait_until="domcontentloaded", timeout=30000)
        else:
            page.reload(wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(2200)
        linked = page.locator("text=Telegram аккаунт привязан").count() > 0
        linked_chat = page.locator("text=chat_id:").count() > 0
        profile_linked = False
        profile_chat_match = False
        for _ in range(8):
            ok_profile, profile_payload = api_get_json("/api/profile")
            if ok_profile:
                profile_linked = bool(profile_payload.get("telegram_linked"))
                profile_chat_match = first_non_empty(profile_payload.get("telegram_id")) == str(args.chat_id)
                if profile_linked:
                    break
            page.wait_for_timeout(900)
        report["steps"]["telegram_link"] = {
            "ok": bool(start_token and webhook_ok and ((linked and linked_chat) or profile_linked)),
            "start_token_present": bool(start_token),
            "webhook_ok": webhook_ok,
            "webhook_status": webhook_status,
            "webhook_body_preview": webhook_body_preview,
            "linked_ui": linked,
            "chat_id_visible": linked_chat,
            "profile_linked": profile_linked,
            "profile_chat_match": profile_chat_match,
        }
        if should_stop("telegram_link"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: telegram")
            print(f"TELEGRAM_LINK_OK: {report['steps']['telegram_link']['ok']}")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["telegram_link"]["ok"] else 1

        # 3) Create shop with bot token + notification chat id
        page.goto(f"{args.base_url}/create-shop", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(1000)
        # Ждем подгрузки лимитов/формы, иначе можем ложно решить, что формы нет.
        try:
            page.wait_for_selector("form.shop-form, .limits-warning, .limit-reached", timeout=12000)
        except Exception:
            pass
        page.wait_for_timeout(1200)
        has_form = page.locator("form.shop-form").count() > 0
        created_shop = False
        if has_form:
            human_type("#name", shop_name)
            human_type("#bot_token", args.bot_token)
            human_type("#delivery_name", "Курьер")
            human_type("#delivery_price", "150")
            human_type("#notification_chat_id", str(args.chat_id))
            page.click('button[type="submit"]:has-text("Создать магазин")')
            page.wait_for_timeout(2800)
            created_shop = page.locator("text=Магазин успешно создан").count() > 0

        auth_token = page.evaluate("() => localStorage.getItem('auth_token')") or ""
        page.goto(f"{args.base_url}/shops", wait_until="domcontentloaded", timeout=30000)
        page.wait_for_timeout(1800)
        shop_card = page.locator(".shop-card", has_text=shop_name).first
        shop_present = shop_card.count() > 0
        settings_href = ""
        products_href = ""
        shop_id = ""
        if shop_present:
            products_link = shop_card.locator('a:has-text("Товары")').first
            products_href = products_link.get_attribute("href") or ""
            if products_href:
                m = re.search(r"/shops/(\d+)/products", products_href)
                if m:
                    shop_id = m.group(1)
                    settings_href = f"/shops/{shop_id}/settings"
            # В текущем UI "Настройки" — это кнопка с пушем роута, а не <a>.
            if not shop_id and shop_card.locator("button.btn-edit").count() > 0:
                shop_card.locator("button.btn-edit").first.click()
                page.wait_for_timeout(800)
                current_url = page.url
                m2 = re.search(r"/shops/(\d+)/settings", current_url)
                if m2:
                    shop_id = m2.group(1)
                    settings_href = f"/shops/{shop_id}/settings"
                    products_href = f"/shops/{shop_id}/products"

        # Fallback: ищем только что созданный магазин через API.
        if not shop_id and auth_token:
            shops_resp = page.request.get(
                f"{args.base_url}/api/shops",
                headers={"Authorization": f"Bearer {auth_token}"},
            )
            if shops_resp.ok:
                payload = shops_resp.json()
                shops = payload.get("shops") or []
                target = None
                for item in shops:
                    if item.get("name") == shop_name:
                        target = item
                        break
                if target:
                    shop_id = str(target.get("id"))
                    settings_href = f"/shops/{shop_id}/settings"
                    products_href = f"/shops/{shop_id}/products"
                    shop_present = True
        create_shop_ok = bool((created_shop or shop_present) and shop_id)
        report["steps"]["create_shop"] = {
            "ok": create_shop_ok,
            "created_shop_message": created_shop,
            "shop_present": shop_present,
            "shop_id": shop_id,
            "has_form": has_form,
        }
        if should_stop("create_shop"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: shop")
            print(f"CREATE_SHOP_OK: {report['steps']['create_shop']['ok']}")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["create_shop"]["ok"] else 1

        # 3.1) Connect bot in settings
        bot_connected = False
        if settings_href:
            page.goto(f"{args.base_url}{settings_href}", wait_until="domcontentloaded", timeout=30000)
            page.wait_for_timeout(2000)
            if page.locator("text=Бот готов").count() > 0:
                bot_connected = True
            if page.locator('button:has-text("Подключить бота")').count() > 0:
                page.click('button:has-text("Подключить бота")')
                page.wait_for_timeout(2600)
            if page.locator('button:has-text("Проверить")').count() > 0:
                page.click('button:has-text("Проверить")')
                page.wait_for_timeout(2200)
                bot_connected = bot_connected or (page.locator("text=Бот готов").count() > 0)

        report["steps"]["connect_bot"] = {
            "ok": bot_connected,
            "settings_url": f"{args.base_url}{settings_href}" if settings_href else "",
        }
        if should_stop("connect_bot"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: connect_bot")
            print(f"CONNECT_BOT_OK: {report['steps']['connect_bot']['ok']}")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["connect_bot"]["ok"] else 1

        # 4) Add category + product + import
        category_created = False
        product_created = False
        product_created_api = False
        import_started = False
        import_completed = False
        imported_product_visible = False
        imported_product_visible_api = False
        if products_href:
            page.goto(f"{args.base_url}{products_href}", wait_until="domcontentloaded", timeout=30000)
            page.wait_for_timeout(1800)
            if page.locator('button:has-text("Категории")').count() > 0:
                page.click('button:has-text("Категории")')
                page.wait_for_timeout(500)
                if page.locator('.categories-modal input[placeholder="Название категории"]').count() > 0:
                    page.locator('.categories-modal input[placeholder="Название категории"]').first.fill(category_name)
                    page.locator('.categories-modal .category-form .btn-primary:has-text("Добавить")').first.click()
                    page.wait_for_timeout(1100)
                    category_created = page.locator(".categories-list .category-name", has_text=category_name).count() > 0
                page.locator('.categories-modal button:has-text("Закрыть")').first.click()
                page.wait_for_timeout(500)

            if page.locator('button:has-text("Добавить товар")').count() > 0:
                page.click('button:has-text("Добавить товар")')
                page.wait_for_timeout(600)
                modal = page.locator(".modal .modal-content", has_text="Создать товар").first
                if modal.count() > 0:
                    modal.locator('.form-group:has(label:has-text("Название")) input').first.fill(product_name)
                    modal.locator('.form-group:has(label:has-text("Цена")) input[type="number"]').first.fill("999")
                    modal.locator('.form-group:has(label:has-text("Описание")) textarea').first.fill(
                        "E2E full flow product description"
                    )
                    categories_select = modal.locator('.form-group:has(label:has-text("Категории")) select').first
                    if categories_select.count() > 0:
                        try:
                            categories_select.select_option(label=category_name)
                        except Exception:
                            # На случай если label не найден из-за нормализации - пробуем по value.
                            ok_categories, payload = api_get_json(f"/api/shops/{shop_id}/products")
                            if ok_categories:
                                api_categories = payload.get("categories") or []
                                for item in api_categories:
                                    if first_non_empty(item.get("name")) == category_name:
                                        categories_select.select_option(str(item.get("id")))
                                        break
                    modal.locator('button[type="submit"]:has-text("Сохранить")').first.click()
                    page.wait_for_timeout(2200)
                    product_created = page.locator(".products-grid .product-card h3", has_text=product_name).count() > 0
                    ok_products, products_payload = api_get_json(
                        f"/api/shops/{shop_id}/products?search={product_name}&category="
                    )
                    if ok_products:
                        api_products = ((products_payload.get("products") or {}).get("data")) or []
                        product_created_api = any(
                            first_non_empty(item.get("name")) == product_name for item in api_products
                        )

            # Импорт через UI модалку
            csv_path = Path("tools/tmp/e2e_import_products.csv")
            csv_path.parent.mkdir(parents=True, exist_ok=True)
            csv_path.write_text(
                "name,price,description,category,in_stock,image\n"
                f"\"{import_product_name}\",\"555\",\"imported by e2e\",\"{category_name}\",\"1\",\"\"\n",
                encoding="utf-8",
            )

            import_btn = page.locator('button:has-text("Импорт из Excel")').first
            import_btn_disabled = False
            if import_btn.count() > 0:
                import_btn_disabled = bool(import_btn.get_attribute("disabled") is not None)
                if not import_btn_disabled:
                    import_btn.click()
                else:
                    report["steps"]["import_product"] = {
                        "ok": False,
                        "import_started": False,
                        "import_completed": False,
                        "imported_product_visible": False,
                        "import_product_name": import_product_name,
                        "error": "Import button is disabled (plan/capability restriction)",
                    }
            if import_btn.count() > 0 and not import_btn_disabled:
                page.wait_for_timeout(700)
                file_input = page.locator('.modal-content input[type="file"]').first
                if file_input.count() > 0:
                    file_input.set_input_files(str(csv_path))
                    page.wait_for_timeout(500)
                    page.click('.modal-content button:has-text("Продолжить")')
                    page.wait_for_timeout(2500)
                    if page.locator('.modal-content button:has-text("Импортировать")').count() > 0:
                        page.click('.modal-content button:has-text("Импортировать")')
                        import_started = True
                        # Ждём завершения (async import status polling в самой модалке)
                        for _ in range(12):
                            modal = page.locator(".modal-content").first
                            if modal.locator(".result.success").count() > 0 and modal.get_by_text("Статус:").count() > 0:
                                break
                            if page.locator('.modal-content button:has-text("Обновить статус")').count() > 0:
                                page.click('.modal-content button:has-text("Обновить статус")')
                            page.wait_for_timeout(2500)
                        import_completed = page.locator('.modal-content .result.success').count() > 0
                        if page.locator('.modal-content button:has-text("Закрыть")').count() > 0:
                            page.click('.modal-content button:has-text("Закрыть")')
                            page.wait_for_timeout(600)
                        page.reload(wait_until="domcontentloaded", timeout=30000)
                        page.wait_for_timeout(1800)
                        imported_product_visible = page.locator(".products-grid .product-card h3", has_text=import_product_name).count() > 0
                        ok_imported, imported_payload = api_get_json(
                            f"/api/shops/{shop_id}/products?search={import_product_name}&category="
                        )
                        if ok_imported:
                            imported_api_products = ((imported_payload.get("products") or {}).get("data")) or []
                            imported_product_visible_api = any(
                                first_non_empty(item.get("name")) == import_product_name
                                for item in imported_api_products
                            )

        report["steps"]["add_product"] = {
            "ok": bool(category_created and (product_created or product_created_api)),
            "category_created": category_created,
            "product_created": product_created,
            "product_created_api": product_created_api,
        }
        if "import_product" not in report["steps"]:
            report["steps"]["import_product"] = {
                "ok": bool(import_started and import_completed and (imported_product_visible or imported_product_visible_api)),
                "import_started": import_started,
                "import_completed": import_completed,
                "imported_product_visible": imported_product_visible,
                "imported_product_visible_api": imported_product_visible_api,
                "import_product_name": import_product_name,
            }
        if should_stop("add_product"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: add_product")
            print(f"ADD_PRODUCT_OK: {report['steps']['add_product']['ok']}")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["add_product"]["ok"] else 1
        if should_stop("import_product"):
            page.screenshot(path=str(Path(args.screenshot)), full_page=True)
            Path(args.report_json).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
            browser.close()
            print(f"EMAIL: {email}")
            print("STOP_AFTER: import")
            print(f"IMPORT_PRODUCT_OK: {report['steps']['import_product']['ok']}")
            print(f"REPORT_JSON: {args.report_json}")
            print(f"SCREENSHOT: {args.screenshot}")
            return 0 if report["steps"]["import_product"]["ok"] else 1

        # 5) Delete account via profile UI (optional)
        deleted_redirect = False
        if args.skip_delete:
            deleted_redirect = True
        else:
            page.goto(f"{args.base_url}/profile", wait_until="domcontentloaded", timeout=30000)
            page.wait_for_timeout(1200)
            delete_btn = page.locator('button:has-text("Удалить аккаунт")').first
            if delete_btn.count() > 0:
                delete_btn.scroll_into_view_if_needed()
                delete_btn.click(force=True)
                page.wait_for_timeout(2500)

            deleted_redirect = "/register" in page.url or page.locator('form.auth-form button:has-text("Зарегистрироваться")').count() > 0
            # Fallback: если UI не удалил, удаляем через API тем же токеном, чтобы cleanup тоже проверить.
            if not deleted_redirect:
                auth_token_after = page.evaluate("() => localStorage.getItem('auth_token')") or auth_token
                if auth_token_after:
                    delete_resp = page.request.delete(
                        f"{args.base_url}/api/profile",
                        headers={"Authorization": f"Bearer {auth_token_after}"},
                    )
                    if delete_resp.ok:
                        page.goto(f"{args.base_url}/register", wait_until="domcontentloaded", timeout=30000)
                        deleted_redirect = True
        report["steps"]["delete_account"] = {
            "ok": deleted_redirect,
            "final_url": page.url,
            "skipped": bool(args.skip_delete),
        }

        screenshot_path = Path(args.screenshot)
        screenshot_path.parent.mkdir(parents=True, exist_ok=True)
        page.screenshot(path=str(screenshot_path), full_page=True)
        report["screenshot"] = str(screenshot_path)

        report_path = Path(args.report_json)
        report_path.parent.mkdir(parents=True, exist_ok=True)
        report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

        if args.keep_open and not args.headless:
            print("")
            print("=== MANUAL STEP: FINAL INSPECTION ===")
            print("Browser is left open for manual review.")
            input("Press Enter to close browser and finish...")

        browser.close()

    required = required_steps_for_run()
    ok = all(bool(report["steps"].get(key, {}).get("ok")) for key in required)

    print(f"EMAIL: {email}")
    print(f"SHOP_NAME: {shop_name}")
    print(f"PRODUCT_NAME: {product_name}")
    print(f"REGISTER_OK: {report['steps']['register']['ok']}")
    print(f"TELEGRAM_LINK_OK: {report['steps']['telegram_link']['ok']}")
    print(
        f"PLAN_OK: {report['steps']['subscribe_plan']['ok']} "
        f"({report['steps']['subscribe_plan'].get('plan_actual', report['steps']['subscribe_plan'].get('plan_expected', 'n/a'))})"
    )
    print(f"CREATE_SHOP_OK: {report['steps']['create_shop']['ok']}")
    print(f"CONNECT_BOT_OK: {report['steps']['connect_bot']['ok']}")
    print(f"ADD_PRODUCT_OK: {report['steps']['add_product']['ok']}")
    print(f"IMPORT_PRODUCT_OK: {report['steps']['import_product']['ok']}")
    print(f"DELETE_ACCOUNT_OK: {report['steps']['delete_account']['ok']}")
    print(f"REPORT_JSON: {args.report_json}")
    print(f"SCREENSHOT: {args.screenshot}")

    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
