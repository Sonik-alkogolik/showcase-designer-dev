from __future__ import annotations

import argparse
import json
import time
import urllib.error
import urllib.request
from dataclasses import dataclass
from pathlib import Path


@dataclass
class HttpResult:
    status: int
    payload: dict
    error: str = ""


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Full service smoke+security audit: registration, login, starter plan, "
            "page traversal, auth/access checks. Can run multiple iterations."
        )
    )
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="Backend base URL")
    parser.add_argument("--iterations", type=int, default=3, help="How many full cycles to run")
    parser.add_argument(
        "--seed-shop-id",
        type=int,
        default=3,
        help="Existing shop id owned by another user for IDOR access check",
    )
    parser.add_argument(
        "--report-path",
        default="tools/full_service_audit_report.json",
        help="Path to JSON report",
    )
    return parser.parse_args()


def http_json(
    url: str,
    method: str = "GET",
    body: dict | None = None,
    token: str = "",
    timeout: int = 20,
) -> HttpResult:
    headers = {"Accept": "application/json"}
    payload_bytes = None
    if token:
        headers["Authorization"] = f"Bearer {token}"
    if body is not None:
        headers["Content-Type"] = "application/json"
        payload_bytes = json.dumps(body).encode("utf-8")

    req = urllib.request.Request(url=url, data=payload_bytes, method=method, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            text = resp.read().decode("utf-8")
            parsed = json.loads(text) if text else {}
            if isinstance(parsed, dict):
                return HttpResult(status=resp.status, payload=parsed)
            return HttpResult(status=resp.status, payload={"raw": parsed})
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8") if e.fp else ""
        parsed: dict
        if raw:
            try:
                decoded = json.loads(raw)
                parsed = decoded if isinstance(decoded, dict) else {"raw": decoded}
            except json.JSONDecodeError:
                parsed = {"raw": raw}
        else:
            parsed = {}
        return HttpResult(status=e.code, payload=parsed, error=str(e))
    except Exception as e:  # noqa: BLE001
        return HttpResult(status=0, payload={}, error=str(e))


def http_status(url: str, timeout: int = 20) -> HttpResult:
    req = urllib.request.Request(url=url, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return HttpResult(status=resp.status, payload={})
    except urllib.error.HTTPError as e:
        return HttpResult(status=e.code, payload={}, error=str(e))
    except Exception as e:  # noqa: BLE001
        return HttpResult(status=0, payload={}, error=str(e))


def run_iteration(base: str, iteration: int, seed_shop_id: int) -> dict:
    stamp = time.strftime("%Y%m%d_%H%M%S")
    email = f"audit_{stamp}_{iteration}@example.com"
    password = "AuditPass123!"

    # 1. Registration
    reg = http_json(
        f"{base}/api/register",
        method="POST",
        body={
            "name": f"Audit User {iteration}",
            "email": email,
            "password": password,
            "password_confirmation": password,
        },
    )

    # 2. Login
    login = http_json(
        f"{base}/api/login",
        method="POST",
        body={"email": email, "password": password},
    )
    token = str(login.payload.get("token") or "")

    # 3. Activate starter plan
    starter = http_json(
        f"{base}/api/subscription/subscribe",
        method="POST",
        token=token,
        body={
            "plan": "starter",
            "auto_renew": False,
            "offer_accepted": True,
            "privacy_accepted": True,
        },
    )

    # 4. Traverse known pages
    page_paths = [
        "/",
        "/login",
        "/register",
        "/privacy",
        "/shops",
        "/create-shop",
        "/profile",
        "/plans",
        "/shops/3/products",
        "/shops/3/settings",
        "/app?shop=3",
    ]
    pages = {path: http_status(f"{base}{path}").status for path in page_paths}

    # 5. Security checks
    # 5.1 Protected API without token
    unauth_profile = http_json(f"{base}/api/profile")

    # 5.2 Auth profile with token
    auth_profile = http_json(f"{base}/api/profile", token=token)

    # 5.3 Shop create should be blocked without Telegram link
    create_shop_block = http_json(
        f"{base}/api/shops",
        method="POST",
        token=token,
        body={
            "name": f"Audit Blocked Shop {iteration}",
            "delivery_name": "Курьер",
            "delivery_price": 100,
            "notification_chat_id": "123456789",
        },
    )

    # 5.4 IDOR check: new user tries to access someone else's shop
    idor_read = http_json(f"{base}/api/shops/{seed_shop_id}", token=token)
    idor_products = http_json(f"{base}/api/shops/{seed_shop_id}/products", token=token)

    return {
        "iteration": iteration,
        "email": email,
        "register_status": reg.status,
        "register_ok": reg.status in (200, 201),
        "login_status": login.status,
        "login_ok": bool(token) and login.status in (200, 201),
        "starter_status": starter.status,
        "starter_ok": starter.status in (200, 201),
        "page_statuses": pages,
        "unauth_profile_status": unauth_profile.status,
        "auth_profile_status": auth_profile.status,
        "create_shop_without_telegram_status": create_shop_block.status,
        "create_shop_without_telegram_message": create_shop_block.payload.get("message", ""),
        "idor_shop_read_status": idor_read.status,
        "idor_shop_products_status": idor_products.status,
        "security_pass": (
            unauth_profile.status == 401
            and auth_profile.status == 200
            and create_shop_block.status == 403
            and idor_read.status == 403
            and idor_products.status == 403
        ),
    }


def main() -> int:
    args = parse_args()
    base = args.base_url.rstrip("/")
    report_path = Path(args.report_path)
    report_path.parent.mkdir(parents=True, exist_ok=True)

    iterations = []
    for i in range(1, max(1, args.iterations) + 1):
        iterations.append(run_iteration(base=base, iteration=i, seed_shop_id=args.seed_shop_id))
        time.sleep(0.4)

    ok_count = sum(1 for item in iterations if item["security_pass"])
    report = {
        "base_url": base,
        "iterations_requested": args.iterations,
        "iterations_executed": len(iterations),
        "security_pass_count": ok_count,
        "all_security_pass": ok_count == len(iterations),
        "runs": iterations,
        "generated_at_unix": int(time.time()),
    }
    report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"AUDIT_RUNS: {len(iterations)}")
    print(f"SECURITY_PASS_COUNT: {ok_count}")
    print(f"ALL_SECURITY_PASS: {report['all_security_pass']}")
    print(f"REPORT_PATH: {report_path}")

    return 0 if report["all_security_pass"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
