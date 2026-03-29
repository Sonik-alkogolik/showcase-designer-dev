from __future__ import annotations

import argparse
import json
import time
import urllib.error
import urllib.request
from pathlib import Path

from playwright.sync_api import sync_playwright


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Extended security audit with user-simulation mode: register/login/plan via API, "
            "browser traversal in visible mode, access-control probes, and login brute-force check."
        )
    )
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="Application base URL")
    parser.add_argument("--owner-email", default="test2@gmail.com", help="Existing owner account email")
    parser.add_argument("--owner-password", default="1234", help="Existing owner account password")
    parser.add_argument("--browser", choices=["chromium", "chrome"], default="chrome")
    parser.add_argument("--headless", action="store_true")
    parser.add_argument("--human", action="store_true")
    parser.add_argument("--slow-ms", type=int, default=140)
    parser.add_argument("--bruteforce-attempts", type=int, default=8)
    parser.add_argument("--report-path", default="tools/e2e_extended_security_audit_report.json")
    parser.add_argument("--screenshot", default="tools/e2e_extended_security_audit.png")
    return parser.parse_args()


def http_json(
    url: str,
    method: str = "GET",
    body: dict | None = None,
    token: str = "",
    timeout: int = 20,
) -> tuple[int, dict]:
    headers = {"Accept": "application/json"}
    payload = None
    if token:
        headers["Authorization"] = f"Bearer {token}"
    if body is not None:
        payload = json.dumps(body).encode("utf-8")
        headers["Content-Type"] = "application/json"

    req = urllib.request.Request(url=url, data=payload, method=method, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            raw = resp.read().decode("utf-8")
            parsed = json.loads(raw) if raw else {}
            if isinstance(parsed, dict):
                return resp.status, parsed
            return resp.status, {"raw": parsed}
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8") if e.fp else ""
        if raw:
            try:
                parsed = json.loads(raw)
                if isinstance(parsed, dict):
                    return e.code, parsed
                return e.code, {"raw": parsed}
            except json.JSONDecodeError:
                return e.code, {"raw": raw}
        return e.code, {}
    except Exception as e:  # noqa: BLE001
        return 0, {"error": str(e)}


def unique_email(prefix: str = "security_audit") -> str:
    return f"{prefix}_{int(time.time())}@example.com"


def first_visible_selector(page, selectors: list[str]) -> str:
    for selector in selectors:
        if page.locator(selector).count():
            return selector
    raise RuntimeError(f"Could not find any selector from: {selectors}")


def main() -> int:
    args = parse_args()
    base = args.base_url.rstrip("/")
    report_path = Path(args.report_path)
    screenshot_path = Path(args.screenshot)
    report_path.parent.mkdir(parents=True, exist_ok=True)
    screenshot_path.parent.mkdir(parents=True, exist_ok=True)

    # 1) Identify foreign shop from existing owner account.
    owner_login_status, owner_login_payload = http_json(
        f"{base}/api/login",
        method="POST",
        body={"email": args.owner_email, "password": args.owner_password},
    )
    owner_token = str(owner_login_payload.get("token") or "")
    foreign_shop_id = None
    owner_shops_status = 0
    if owner_token:
        owner_shops_status, owner_shops_payload = http_json(f"{base}/api/shops", token=owner_token)
        shops = owner_shops_payload.get("shops", []) if isinstance(owner_shops_payload, dict) else []
        if shops:
            try:
                foreign_shop_id = int(shops[0]["id"])
            except Exception:  # noqa: BLE001
                foreign_shop_id = None

    # 2) Register attacker user and activate starter plan.
    attacker_email = unique_email()
    attacker_password = "AuditPass123!"
    reg_status, reg_payload = http_json(
        f"{base}/api/register",
        method="POST",
        body={
            "name": "Security Audit User",
            "email": attacker_email,
            "password": attacker_password,
            "password_confirmation": attacker_password,
        },
    )
    login_status, login_payload = http_json(
        f"{base}/api/login",
        method="POST",
        body={"email": attacker_email, "password": attacker_password},
    )
    attacker_token = str(login_payload.get("token") or "")
    starter_status, starter_payload = http_json(
        f"{base}/api/subscription/subscribe",
        method="POST",
        token=attacker_token,
        body={
            "plan": "starter",
            "auto_renew": False,
            "offer_accepted": True,
            "privacy_accepted": True,
        },
    )

    # 3) Browser simulation: login and traverse pages including foreign shop URLs.
    browser_pages_status: dict[str, int] = {}
    captured_api: list[dict[str, object]] = []
    browser_error = ""

    with sync_playwright() as p:
        launch_args = {"headless": args.headless}
        if args.human:
            launch_args["slow_mo"] = args.slow_ms
        if args.browser == "chrome":
            launch_args["channel"] = "chrome"
        browser = p.chromium.launch(**launch_args)
        page = browser.new_page()

        def on_response(resp) -> None:  # type: ignore[no-untyped-def]
            url = resp.url
            if "/api/" in url:
                if foreign_shop_id and f"/shops/{foreign_shop_id}" not in url:
                    return
                captured_api.append(
                    {
                        "url": url,
                        "status": resp.status,
                        "method": resp.request.method,
                    }
                )

        page.on("response", on_response)

        try:
            page.goto(f"{base}/login", wait_until="domcontentloaded", timeout=30000)
            email_selector = first_visible_selector(
                page,
                ['input[placeholder="you@example.com"]', 'input[placeholder="Email"]', 'input[type="email"]'],
            )
            password_selector = first_visible_selector(
                page,
                ['input[placeholder="Ваш пароль"]', 'input[placeholder="Пароль"]', 'input[type="password"]'],
            )
            submit_selector = first_visible_selector(page, ['button[type="submit"]', 'button:has-text("Войти")'])

            page.fill(email_selector, attacker_email)
            page.fill(password_selector, attacker_password)
            page.click(submit_selector)
            page.wait_for_timeout(1800)

            paths = ["/shops", "/create-shop", "/plans", "/profile", "/privacy"]
            if foreign_shop_id:
                paths.extend([f"/shops/{foreign_shop_id}/products", f"/shops/{foreign_shop_id}/settings"])

            for path in paths:
                try:
                    resp = page.goto(f"{base}{path}", wait_until="domcontentloaded", timeout=30000)
                    browser_pages_status[path] = resp.status if resp else 0
                    page.wait_for_timeout(900)
                except Exception:  # noqa: BLE001
                    browser_pages_status[path] = 0

            page.screenshot(path=str(screenshot_path), full_page=True)
        except Exception as e:  # noqa: BLE001
            browser_error = str(e)
            try:
                page.screenshot(path=str(screenshot_path), full_page=True)
            except Exception:  # noqa: BLE001
                pass
        finally:
            browser.close()

    # 4) API-level security probes as attacker.
    unauth_profile_status, _ = http_json(f"{base}/api/profile")
    auth_profile_status, _ = http_json(f"{base}/api/profile", token=attacker_token)

    idor_statuses: dict[str, int] = {}
    if foreign_shop_id:
        endpoints = {
            "get_shop": (f"{base}/api/shops/{foreign_shop_id}", "GET", None),
            "update_shop": (
                f"{base}/api/shops/{foreign_shop_id}",
                "PATCH",
                {"name": "HACKED NAME"},
            ),
            "delete_shop": (f"{base}/api/shops/{foreign_shop_id}", "DELETE", None),
            "list_products": (f"{base}/api/shops/{foreign_shop_id}/products", "GET", None),
            "create_product": (
                f"{base}/api/shops/{foreign_shop_id}/products",
                "POST",
                {"name": "HACK PRODUCT", "price": 1, "description": "x", "in_stock": True},
            ),
            "list_orders": (f"{base}/api/shops/{foreign_shop_id}/orders", "GET", None),
        }
        for key, (url, method, body) in endpoints.items():
            status, _ = http_json(url, method=method, body=body, token=attacker_token)
            idor_statuses[key] = status

    # 5) Login brute-force observation.
    brute_statuses = []
    brute_email = unique_email("bf")
    for _ in range(max(1, args.bruteforce_attempts)):
        status, _ = http_json(
            f"{base}/api/login",
            method="POST",
            body={"email": brute_email, "password": "wrong-password"},
        )
        brute_statuses.append(status)
        time.sleep(0.08)

    saw_rate_limit = any(s == 429 for s in brute_statuses)

    findings = []
    if not saw_rate_limit:
        findings.append(
            {
                "severity": "medium",
                "title": "No login rate-limit observed in brute-force check",
                "details": f"{args.bruteforce_attempts} invalid login attempts returned statuses: {brute_statuses}",
            }
        )

    if foreign_shop_id and any(status != 403 for status in idor_statuses.values()):
        findings.append(
            {
                "severity": "high",
                "title": "Possible IDOR on shop-scoped endpoints",
                "details": idor_statuses,
            }
        )

    report = {
        "base_url": base,
        "owner_login_status": owner_login_status,
        "owner_shops_status": owner_shops_status,
        "foreign_shop_id": foreign_shop_id,
        "attacker_email": attacker_email,
        "register_status": reg_status,
        "login_status": login_status,
        "starter_status": starter_status,
        "starter_message": starter_payload.get("message", ""),
        "browser_pages_status": browser_pages_status,
        "browser_error": browser_error,
        "captured_foreign_shop_api": captured_api,
        "unauth_profile_status": unauth_profile_status,
        "auth_profile_status": auth_profile_status,
        "idor_statuses": idor_statuses,
        "bruteforce_statuses": brute_statuses,
        "saw_rate_limit_429": saw_rate_limit,
        "findings": findings,
        "all_critical_controls_ok": (
            reg_status in (200, 201)
            and login_status in (200, 201)
            and starter_status in (200, 201)
            and unauth_profile_status == 401
            and auth_profile_status == 200
            and (not idor_statuses or all(v == 403 for v in idor_statuses.values()))
        ),
        "screenshot": str(screenshot_path),
        "generated_at_unix": int(time.time()),
    }
    report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"FOREIGN_SHOP_ID: {foreign_shop_id}")
    print(f"ATTACKER_EMAIL: {attacker_email}")
    print(f"REGISTER_STATUS: {reg_status}")
    print(f"LOGIN_STATUS: {login_status}")
    print(f"STARTER_STATUS: {starter_status}")
    print(f"UNAUTH_PROFILE_STATUS: {unauth_profile_status}")
    print(f"AUTH_PROFILE_STATUS: {auth_profile_status}")
    print(f"IDOR_STATUSES: {json.dumps(idor_statuses, ensure_ascii=False)}")
    print(f"BRUTEFORCE_STATUSES: {brute_statuses}")
    print(f"SAW_RATE_LIMIT_429: {saw_rate_limit}")
    print(f"FINDINGS_COUNT: {len(findings)}")
    print(f"REPORT_PATH: {report_path}")
    print(f"SCREENSHOT: {screenshot_path}")

    return 0 if report["all_critical_controls_ok"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
