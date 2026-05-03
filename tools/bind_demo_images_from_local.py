from __future__ import annotations

import argparse
import mimetypes
from pathlib import Path
from typing import Any

import requests


SHOP_FOLDER_MAP: dict[str, str] = {
    "DEMO Маникюр Studio": "shop-16",
    "DEMO Barber Club": "shop-17",
    "DEMO Auto Service": "shop-18",
    "DEMO Эвакуатор 24/7": "shop-19",
    "DEMO Food Delivery": "shop-20",
}


def as_str(value: Any) -> str:
    if value is None:
        return ""
    return str(value)


def sorted_image_files(folder: Path) -> list[Path]:
    files = [
        p
        for p in folder.iterdir()
        if p.is_file() and p.suffix.lower() in {".jpg", ".jpeg", ".png", ".webp"}
    ]
    files.sort(key=lambda p: int(p.stem) if p.stem.isdigit() else 10_000_000)
    return files


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Bind local demo images to demo products via API upload (image_file)."
    )
    parser.add_argument("--base-url", default="https://e-tgo.ru", help="App base URL")
    parser.add_argument("--email", required=True, help="User email for /api/login")
    parser.add_argument("--password", required=True, help="User password for /api/login")
    parser.add_argument(
        "--images-root",
        default="client/public/demo-images",
        help="Local root with shop-16..shop-20 folders",
    )
    parser.add_argument(
        "--per-page",
        type=int,
        default=300,
        help="Products page size for loading demo products",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Only print what would be uploaded, without PUT requests",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    base_url = args.base_url.rstrip("/")
    images_root = Path(args.images_root)

    session = requests.Session()

    login = session.post(
        f"{base_url}/api/login",
        json={"email": args.email, "password": args.password},
        timeout=30,
    )
    login.raise_for_status()
    token = login.json().get("token")
    if not token:
        raise RuntimeError("No auth token from /api/login")

    headers = {"Authorization": f"Bearer {token}"}

    shops_resp = session.get(f"{base_url}/api/shops", headers=headers, timeout=30)
    shops_resp.raise_for_status()
    shops = shops_resp.json().get("shops", [])

    shops_by_name = {s.get("name"): s for s in shops}

    total_updated = 0
    total_failed = 0

    for shop_name, folder_name in SHOP_FOLDER_MAP.items():
        shop = shops_by_name.get(shop_name)
        if not shop:
            print(f"SKIP shop-not-found: {shop_name}")
            continue

        shop_id = int(shop["id"])
        folder = images_root / folder_name
        if not folder.exists():
            print(f"SKIP no-folder: {folder}")
            continue

        image_files = sorted_image_files(folder)
        if not image_files:
            print(f"SKIP no-images: {folder}")
            continue

        products_resp = session.get(
            f"{base_url}/api/shops/{shop_id}/products",
            params={"per_page": args.per_page},
            headers=headers,
            timeout=40,
        )
        products_resp.raise_for_status()
        payload = products_resp.json().get("products", {})
        items = payload.get("data", payload if isinstance(payload, list) else [])
        items = sorted(items, key=lambda p: int(p.get("id", 0)))

        print(f"SHOP {shop_name} (id={shop_id}) products={len(items)} images={len(image_files)}")

        for idx, product in enumerate(items):
            product_id = int(product["id"])
            image_path = image_files[idx % len(image_files)]

            if args.dry_run:
                print(f"DRY shop={shop_id} product={product_id} <- {image_path.name}")
                continue

            mime = mimetypes.guess_type(image_path.name)[0] or "application/octet-stream"
            data = {
                "name": as_str(product.get("name")),
                "price": as_str(product.get("price")),
                "description": as_str(product.get("description")),
                "category": as_str(product.get("category")),
                "in_stock": "1" if product.get("in_stock") else "0",
                "show_in_slider": "1" if product.get("show_in_slider") else "0",
            }

            # The app expects multipart update as POST + _method=PUT (same as frontend behavior).
            data["_method"] = "PUT"
            with image_path.open("rb") as fh:
                files = {"image_file": (image_path.name, fh, mime)}
                resp = session.post(
                    f"{base_url}/api/shops/{shop_id}/products/{product_id}",
                    headers=headers,
                    data=data,
                    files=files,
                    timeout=80,
                )

            ok_json = False
            try:
                payload = resp.json()
                ok_json = bool(payload.get("success"))
            except Exception:
                ok_json = False

            if resp.ok and ok_json:
                total_updated += 1
            else:
                total_failed += 1
                print(
                    f"FAIL shop={shop_id} product={product_id} status={resp.status_code} body={resp.text[:180]}"
                )

    print(f"RESULT updated={total_updated} failed={total_failed}")
    return 0 if total_failed == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
