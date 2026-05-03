from __future__ import annotations

import io
from typing import Any

import requests

BASE_URL = "https://e-tgo.ru"
EMAIL = "dmitribelitskij@gmail.com"
PASSWORD = "934kifg9rtj3j9tgre5"

TARGET_SHOPS = {
    "DEMO Маникюр Studio",
    "DEMO Barber Club",
    "DEMO Auto Service",
    "DEMO Эвакуатор 24/7",
    "DEMO Food Delivery",
}


def as_str(v: Any) -> str:
    if v is None:
        return ""
    return str(v)


def main() -> int:
    s = requests.Session()
    login = s.post(
        f"{BASE_URL}/api/login",
        json={"email": EMAIL, "password": PASSWORD},
        timeout=30,
    )
    login.raise_for_status()
    token = login.json().get("token")
    if not token:
        raise RuntimeError("No token from /api/login")
    headers = {"Authorization": f"Bearer {token}"}

    shops_resp = s.get(f"{BASE_URL}/api/shops", headers=headers, timeout=30)
    shops_resp.raise_for_status()
    shops = shops_resp.json().get("shops", [])

    target = [x for x in shops if x.get("name") in TARGET_SHOPS]
    updated = 0
    failed = 0

    for shop in target:
        shop_id = shop["id"]
        products_resp = s.get(
            f"{BASE_URL}/api/shops/{shop_id}/products",
            params={"per_page": 300},
            headers=headers,
            timeout=30,
        )
        products_resp.raise_for_status()
        payload = products_resp.json().get("products", {})
        items = payload.get("data", payload if isinstance(payload, list) else [])

        for p in items:
            src = p.get("image") or f"https://picsum.photos/seed/shop-{shop_id}-prod-{p['id']}/1200/800"
            try:
                img = s.get(src, timeout=40)
                img.raise_for_status()
                content_type = img.headers.get("Content-Type", "image/jpeg").split(";")[0]
                ext = "jpg"
                if "png" in content_type:
                    ext = "png"
                elif "webp" in content_type:
                    ext = "webp"

                files = {
                    "image_file": (f"shop{shop_id}_product{p['id']}.{ext}", io.BytesIO(img.content), content_type),
                }
                data = {
                    "name": as_str(p.get("name")),
                    "price": as_str(p.get("price")),
                    "description": as_str(p.get("description")),
                    "category": as_str(p.get("category")),
                    "in_stock": "1" if p.get("in_stock") else "0",
                    "show_in_slider": "1" if p.get("show_in_slider") else "0",
                }
                r = s.put(
                    f"{BASE_URL}/api/shops/{shop_id}/products/{p['id']}",
                    headers=headers,
                    data=data,
                    files=files,
                    timeout=60,
                )
                if r.ok:
                    updated += 1
                else:
                    failed += 1
            except Exception:
                failed += 1

    print(f"updated={updated}; failed={failed}")
    return 0 if failed == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())

