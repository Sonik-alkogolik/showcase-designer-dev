from __future__ import annotations

import argparse
import random
from time import sleep

from playwright.sync_api import Page, sync_playwright


SHOPS = [
    ("DEMO Маникюр Studio", "nails,manicure", "Маникюр услуга"),
    ("DEMO Barber Club", "barber,haircut", "Барбер услуга"),
    ("DEMO Auto Service", "car,service", "Автоуслуга"),
    ("DEMO Эвакуатор 24/7", "tow,truck", "Эвакуация"),
    ("DEMO Food Delivery", "food,delivery", "Блюдо"),
]


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Delete all products and refill via UI")
    p.add_argument("--base-url", default="https://e-tgo.ru")
    p.add_argument("--email", required=True)
    p.add_argument("--password", required=True)
    p.add_argument("--per-shop", type=int, default=20)
    p.add_argument("--slow-ms", type=int, default=240)
    p.add_argument("--pause-factor", type=float, default=1.0, help="0.2 = в 5 раз быстрее паузы")
    return p.parse_args()


PAUSE_FACTOR = 1.0


def pause(a: float = 0.25, b: float = 0.7) -> None:
    sleep(random.uniform(a, b) * PAUSE_FACTOR)


def human_type(page: Page, selector: str, text: str, scope=None) -> None:
    target = (scope.locator(selector).first if scope is not None else page.locator(selector).first)
    target.click()
    page.keyboard.press("Control+a")
    page.keyboard.press("Backspace")
    for ch in text:
        page.keyboard.type(ch, delay=45)
    pause(0.08, 0.2)


def login(page: Page, base_url: str, email: str, password: str) -> None:
    page.goto(f"{base_url}/login", wait_until="domcontentloaded", timeout=60000)
    human_type(page, 'input[type="email"]', email)
    human_type(page, 'input[type="password"]', password)
    page.click('button[type="submit"]')
    page.wait_for_timeout(2500)


def open_products(page: Page, base_url: str, shop_name: str) -> bool:
    page.goto(f"{base_url}/shops", wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(1400)
    card = page.locator(".shop-card", has_text=shop_name).first
    if card.count() == 0:
        return False
    link = card.locator('a:has-text("Товары")').first
    if link.count() == 0:
        return False
    link.click()
    page.wait_for_timeout(1600)
    return True


def delete_all_products_ui(page: Page) -> int:
    deleted = 0
    while True:
        btn = page.locator(".products-grid .product-card .btn-delete").first
        if btn.count() == 0:
            break
        btn.click()
        page.wait_for_timeout(500)
        deleted += 1
        pause(0.1, 0.25)
    page.wait_for_timeout(1000)
    return deleted


def create_product_ui(page: Page, title: str, query: str, idx: int) -> bool:
    add = page.locator('button:has-text("Добавить товар")').first
    if add.count() == 0:
        return False
    add.click()
    page.wait_for_timeout(450)
    modal = page.locator(".modal .modal-content").first
    if modal.count() == 0:
        return False

    human_type(page, '.form-group input:not([type="number"]):not([type="file"])', f"{title} #{idx}", scope=modal)
    human_type(page, '.form-group input[type="number"]', str(400 + idx * 75), scope=modal)
    human_type(page, ".form-group textarea", f"Демо-описание товара #{idx}", scope=modal)
    human_type(page, 'input[placeholder="https://..."]', f"https://source.unsplash.com/1200x800/?{query}&sig={idx}", scope=modal)

    modal.locator('button[type="submit"]:has-text("Сохранить")').first.click()
    page.wait_for_timeout(1200)
    return True


def main() -> int:
    args = parse_args()
    global PAUSE_FACTOR
    PAUSE_FACTOR = max(0.05, args.pause_factor)
    with sync_playwright() as p:
        browser = p.chromium.launch(channel="chrome", headless=False, slow_mo=args.slow_ms)
        page = browser.new_page()
        page.on("dialog", lambda d: d.accept())

        login(page, args.base_url, args.email, args.password)

        for shop_name, query, base_title in SHOPS:
            if not open_products(page, args.base_url, shop_name):
                continue

            delete_all_products_ui(page)
            for i in range(1, args.per_shop + 1):
                ok = create_product_ui(page, base_title, query, i)
                if not ok:
                    break
                pause(0.2, 0.55)
                if i % 5 == 0:
                    page.wait_for_timeout(900)

        page.wait_for_timeout(2000)
        browser.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
