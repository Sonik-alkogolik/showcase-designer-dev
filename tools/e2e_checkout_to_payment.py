import argparse
import json
import time
import urllib.error
import urllib.request
import webbrowser
from pathlib import Path


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Smoke checkout/payment flow: preflight checks -> create order -> open YooKassa URL (optional)."
    )
    parser.add_argument("--base-url", default="http://127.0.0.1:8000", help="Backend base URL")
    parser.add_argument("--shop-id", default="2", help="Shop id to use for public products and order")
    parser.add_argument(
        "--public-url",
        default="",
        help="Optional public URL (tunnel) for external app check, e.g. https://xxx.loca.lt",
    )
    parser.add_argument(
        "--check-public-app",
        action="store_true",
        help="Also verify external URL /app?shop=<id> before order creation",
    )
    parser.add_argument("--customer-name", default="E2E Payment Test", help="Customer name")
    parser.add_argument("--phone", default="+79990000000", help="Customer phone")
    parser.add_argument("--quantity", type=int, default=1, help="Quantity for the selected product")
    parser.add_argument(
        "--allow-draft-fallback",
        action="store_true",
        help="If payment creation is unavailable, create draft order with create_payment=false",
    )
    parser.add_argument(
        "--payment-status-polls",
        type=int,
        default=8,
        help="How many times to poll /api/orders/payment/{paymentId} after order creation",
    )
    parser.add_argument(
        "--payment-status-interval",
        type=float,
        default=2.5,
        help="Seconds between payment status polls",
    )
    parser.add_argument(
        "--report-path",
        default="tools/e2e_checkout_to_payment_report.json",
        help="Path to save run report",
    )
    parser.add_argument(
        "--no-open-browser",
        action="store_true",
        help="Do not open confirmation URL in browser",
    )
    return parser.parse_args()


def http_json(url: str, method: str = "GET", body: dict | None = None) -> tuple[int, dict]:
    data_bytes = None
    headers = {"Accept": "application/json"}
    if body is not None:
        data_bytes = json.dumps(body).encode("utf-8")
        headers["Content-Type"] = "application/json"

    req = urllib.request.Request(url=url, data=data_bytes, method=method, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            raw = resp.read().decode("utf-8")
            return resp.status, json.loads(raw) if raw else {}
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8") if e.fp else ""
        try:
            parsed = json.loads(raw) if raw else {"message": str(e)}
        except json.JSONDecodeError:
            parsed = {"message": raw or str(e)}
        return e.code, parsed


def http_status(url: str, timeout: int = 20) -> tuple[int, str]:
    req = urllib.request.Request(url=url, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return resp.status, ""
    except urllib.error.HTTPError as e:
        return e.code, ""
    except Exception as e:  # noqa: BLE001
        return 0, str(e)


def main() -> int:
    args = parse_args()
    base = args.base_url.rstrip("/")
    public_base = args.public_url.rstrip("/")
    report_path = Path(args.report_path)
    report_path.parent.mkdir(parents=True, exist_ok=True)
    app_path = f"/app?shop={args.shop_id}"

    local_app_status, local_app_error = http_status(f"{base}{app_path}")
    public_app_status = None
    public_app_error = ""
    if args.check_public_app and public_base:
        public_app_status, public_app_error = http_status(f"{public_base}{app_path}")

    shop_public_status, shop_public_payload = http_json(f"{base}/api/shops/{args.shop_id}/public")
    products_status, products_payload = http_json(f"{base}/api/shops/{args.shop_id}/products/public")
    likely_wrong_backend = False
    wrong_backend_hint = ""

    if shop_public_status == 404 and products_status == 404:
        message = ""
        trace_file = ""
        if isinstance(products_payload, dict):
            message = str(products_payload.get("message") or "")
            trace = products_payload.get("trace")
            if isinstance(trace, list) and trace:
                first = trace[0]
                if isinstance(first, dict):
                    trace_file = str(first.get("file") or "")
        if "route api/shops" in message.lower() and "could not be found" in message.lower():
            likely_wrong_backend = True
            if trace_file:
                wrong_backend_hint = f"possible_foreign_backend_trace={trace_file}"

    products = products_payload.get("products", []) if isinstance(products_payload, dict) else []
    in_stock = [p for p in products if bool(p.get("in_stock", True))]
    target = in_stock[0] if in_stock else (products[0] if products else None)

    if products_status != 200 or not target:
        report = {
            "ok": False,
            "step": "load_products",
            "local_app_status": local_app_status,
            "local_app_error": local_app_error,
            "public_app_status": public_app_status,
            "public_app_error": public_app_error,
            "shop_public_status": shop_public_status,
            "status": products_status,
            "likely_wrong_backend": likely_wrong_backend,
            "wrong_backend_hint": wrong_backend_hint,
            "payload": products_payload,
            "message": "No products available for payment test.",
        }
        report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
        print("PAYMENT_E2E_OK: false")
        print(f"REPORT_PATH: {report_path}")
        print(f"LOAD_PRODUCTS_STATUS: {products_status}")
        if likely_wrong_backend:
            print("LIKELY_WRONG_BACKEND_ON_BASE_URL: true")
            if wrong_backend_hint:
                print(f"WRONG_BACKEND_HINT: {wrong_backend_hint}")
        return 1

    order_body = {
        "shop_id": int(args.shop_id),
        "customer_name": args.customer_name,
        "phone": args.phone,
        "items": [
            {
                "id": int(target["id"]),
                "quantity": int(args.quantity),
            }
        ],
        "create_payment": True,
    }

    order_status, order_payload = http_json(f"{base}/api/orders", method="POST", body=order_body)
    fallback_used = False
    fallback_reason = ""
    confirmation_url = order_payload.get("confirmation_url") if isinstance(order_payload, dict) else None
    payment_id = order_payload.get("payment_id") if isinstance(order_payload, dict) else None
    order_id = None
    if isinstance(order_payload, dict) and isinstance(order_payload.get("order"), dict):
        order_id = order_payload["order"].get("id")

    payment_unavailable = (
        order_status == 422
        and isinstance(order_payload, dict)
        and isinstance(order_payload.get("errors"), dict)
        and "create_payment" in order_payload.get("errors", {})
    )

    if payment_unavailable and args.allow_draft_fallback:
        fallback_used = True
        fallback_reason = "create_payment_unavailable"
        fallback_status, fallback_payload = http_json(
            f"{base}/api/orders",
            method="POST",
            body={**order_body, "create_payment": False},
        )
        order_status = fallback_status
        order_payload = fallback_payload
        confirmation_url = order_payload.get("confirmation_url") if isinstance(order_payload, dict) else None
        payment_id = order_payload.get("payment_id") if isinstance(order_payload, dict) else None
        if isinstance(order_payload, dict) and isinstance(order_payload.get("order"), dict):
            order_id = order_payload["order"].get("id")

    payment_status_timeline: list[str] = []
    if payment_id:
        for _ in range(max(0, args.payment_status_polls)):
            poll_status, poll_payload = http_json(f"{base}/api/orders/payment/{payment_id}")
            if poll_status != 200:
                payment_status_timeline.append(f"http_{poll_status}")
                break
            current_status = ""
            if isinstance(poll_payload, dict):
                current_status = str(poll_payload.get("status") or "").strip()
            payment_status_timeline.append(current_status or "unknown")
            if current_status in {"paid", "cancelled"}:
                break
            time.sleep(max(0.0, float(args.payment_status_interval)))

    report = {
        "ok": order_status in (200, 201),
        "base_url": base,
        "public_url": public_base or None,
        "local_app_status": local_app_status,
        "local_app_error": local_app_error,
        "public_app_status": public_app_status,
        "public_app_error": public_app_error,
        "shop_public_status": shop_public_status,
        "shop_id": args.shop_id,
        "product_id": target.get("id"),
        "order_status": order_status,
        "order_id": order_id,
        "payment_id": payment_id,
        "confirmation_url": confirmation_url,
        "payment_status_timeline": payment_status_timeline,
        "fallback_used": fallback_used,
        "fallback_reason": fallback_reason,
        "order_payload": order_payload,
        "created_at_unix": int(time.time()),
    }
    report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    if order_status not in (200, 201):
        print("PAYMENT_E2E_OK: false")
        print(f"ORDER_STATUS: {order_status}")
        print(f"ORDER_PAYLOAD: {json.dumps(order_payload, ensure_ascii=False)}")
        print(f"REPORT_PATH: {report_path}")
        return 2

    if not confirmation_url:
        print("PAYMENT_E2E_OK: true")
        print("ORDER_CREATED_BUT_NO_CONFIRMATION_URL: true")
        print("PAYMENT_FLOW_MODE: draft_or_payment_unavailable")
        print(f"ORDER_ID: {order_id}")
        print(f"PAYMENT_ID: {payment_id}")
        print(f"LOCAL_APP_STATUS: {local_app_status}")
        if public_app_status is not None:
            print(f"PUBLIC_APP_STATUS: {public_app_status}")
        if fallback_used:
            print(f"FALLBACK_USED: {fallback_used}")
        print(f"REPORT_PATH: {report_path}")
        return 0

    if not args.no_open_browser:
        webbrowser.open(confirmation_url, new=2)

    print("PAYMENT_E2E_OK: true")
    print(f"ORDER_ID: {order_id}")
    print(f"PAYMENT_ID: {payment_id}")
    print(f"CONFIRMATION_URL: {confirmation_url}")
    if payment_status_timeline:
        print(f"PAYMENT_STATUS_TIMELINE: {' -> '.join(payment_status_timeline)}")
    print(f"LOCAL_APP_STATUS: {local_app_status}")
    if public_app_status is not None:
        print(f"PUBLIC_APP_STATUS: {public_app_status}")
    if fallback_used:
        print(f"FALLBACK_USED: {fallback_used}")
    print("SCRIPT_STOPPED_AFTER_OPENING_PAYMENT_PAGE: true")
    print(f"REPORT_PATH: {report_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
