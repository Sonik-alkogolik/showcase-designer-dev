from __future__ import annotations

import argparse
import random
from time import sleep

from playwright.sync_api import Page, sync_playwright


SHOPS = [
    ("DEMO Маникюр Studio", "nails,manicure", "Маникюр demo"),
    ("DEMO Barber Club", "barber,haircut", "Барбер demo"),
    ("DEMO Auto Service", "car,service", "Автосервис demo"),
    ("DEMO Эвакуатор 24/7", "tow,truck", "Эвакуатор demo"),
    ("DEMO Food Delivery", "food,delivery", "Доставка demo"),
]


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Fill remaining products via UI in visible browser")
    p.add_argument("--base-url", default="https://e-tgo.ru")
    p.add_argument("--email", required=True)
    p.add_argument("--password", required=True)
    p.add_argument("--slow-ms", type=int, default=260)
    return p.parse_args()


def pause(a: float = 0.35, b: float = 0.95) -> None:
    sleep(random.uniform(a, b))


def human_type(page_or_locator: Page, selector: str, text: str) -> None:
    page_or_locator.click(selector)
    page_or_locator.keyboard.press("Control+a")
    page_or_locator.keyboard.press("Backspace")
    for ch in text:
        page_or_locator.keyboard.type(ch, delay=45)
    pause(0.1, 0.25)


def add_product(page: Page, title: str, query: str, seed: int) -> bool:
    add = page.locator('button:has-text("Добавить товар")').first
    if add.count() == 0:
        return False
    add.click()
    page.wait_for_timeout(600)
    modal = page.locator(".modal .modal-content").first
    if modal.count() == 0:
        return False
    human_type(modal, '.form-group input:not([type="number"]):not([type="file"])', f"{title} #{seed}")
    human_type(modal, '.form-group input[type="number"]', str(450 + seed * 35))
    human_type(modal, ".form-group textarea", "Демо-товар, добавлен через пользовательский интерфейс")
    human_type(modal, 'input[placeholder="https://..."]', f"https://source.unsplash.com/1200x800/?{query}&sig={seed}")
    pause()
    modal.locator('button[type="submit"]:has-text("Сохранить")').first.click()
    page.wait_for_timeout(1800)
    return True


def main() -> int:
    args = parse_args()

    with sync_playwright() as p:
        browser = p.chromium.launch(channel="chrome", headless=False, slow_mo=args.slow_ms)
        page = browser.new_page()

        page.goto(f"{args.base_url}/login", wait_until="domcontentloaded", timeout=60000)
        human_type(page, 'input[type="email"]', args.email)
        human_type(page, 'input[type="password"]', args.password)
        page.click('button[type="submit"]')
        page.wait_for_timeout(3000)

        for idx, (shop_name, query, title) in enumerate(SHOPS, start=1):
            page.goto(f"{args.base_url}/shops", wait_until="domcontentloaded", timeout=60000)
            page.wait_for_timeout(1800)
            card = page.locator(".shop-card", has_text=shop_name).first
            if card.count() == 0:
                continue
            link = card.locator('a:has-text("Товары")').first
            href = link.get_attribute("href") or ""
            if not href:
                continue
            link.click()
            page.wait_for_timeout(1800)
            add_product(page, title, query, seed=100 + idx)
            pause(0.8, 1.6)

        page.wait_for_timeout(2500)
        browser.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

