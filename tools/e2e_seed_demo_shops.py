from __future__ import annotations

import argparse
import random
from dataclasses import dataclass
from time import sleep

from playwright.sync_api import Page, sync_playwright


@dataclass
class DemoShop:
    name: str
    niche: str
    bot_token: str
    bot_username: str
    delivery_name: str
    delivery_price: str


DEMO_SHOPS: list[DemoShop] = [
    DemoShop(
        name="DEMO Маникюр Studio",
        niche="manicure",
        bot_token="8617968727:AAHmQVxoK07CyB3Z0VLcKjJ3w7Bq9Uy5B8U",
        bot_username="@tgo_demo_nails_bot",
        delivery_name="Запись в салон",
        delivery_price="0",
    ),
    DemoShop(
        name="DEMO Barber Club",
        niche="barber",
        bot_token="7875393871:AAG3DTwd41u43IM2GBaVis5UT8dN1uU00J8",
        bot_username="@tgo_demo_barber_bot",
        delivery_name="Запись к мастеру",
        delivery_price="0",
    ),
    DemoShop(
        name="DEMO Auto Service",
        niche="autoservice",
        bot_token="8565946564:AAFf8tcQ_jhhoFaC1_gr6nb6WWmv2thVElM",
        bot_username="@tgo_demo_autoservice_bot",
        delivery_name="Выезд мастера",
        delivery_price="500",
    ),
    DemoShop(
        name="DEMO Эвакуатор 24/7",
        niche="evacuator",
        bot_token="8702029994:AAEdcFeedWsgCjLe2e1wjza3RZWu_AnqabA",
        bot_username="@tgo_demo_evac_bot",
        delivery_name="Подача эвакуатора",
        delivery_price="1500",
    ),
    DemoShop(
        name="DEMO Food Delivery",
        niche="food",
        bot_token="8692659410:AAH9RPEy9I5DyHKS2frFn8YFszroWCRvFu8",
        bot_username="@tgo_demo_fooddelivery_bot",
        delivery_name="Доставка",
        delivery_price="250",
    ),
]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Seed 5 demo shops and products via visible browser flow")
    parser.add_argument("--base-url", default="https://e-tgo.ru")
    parser.add_argument("--email", required=True)
    parser.add_argument("--password", required=True)
    parser.add_argument("--products-per-shop", type=int, default=20)
    parser.add_argument("--browser", choices=["chromium", "chrome"], default="chrome")
    parser.add_argument("--headless", action="store_true")
    parser.add_argument("--human", action="store_true")
    parser.add_argument("--slow-ms", type=int, default=120)
    return parser.parse_args()


def products_for_niche(niche: str, count: int) -> list[dict[str, str]]:
    catalog: dict[str, list[tuple[str, int, str, str]]] = {
        "manicure": [
            ("Маникюр классический", 1200, "Аккуратная обработка и покрытие", "nails,manicure"),
            ("Маникюр + гель-лак", 1800, "Стойкое покрытие до 3 недель", "gel,nails"),
            ("Укрепление ногтей", 1600, "Укрепление базой и выравнивание", "nail,beauty"),
            ("Дизайн 2 ногтей", 700, "Минималистичный дизайн", "nail,design"),
        ],
        "barber": [
            ("Мужская стрижка", 1200, "Стрижка машинкой и ножницами", "barber,haircut"),
            ("Стрижка + борода", 1800, "Комплексная услуга", "beard,barber"),
            ("Оформление бороды", 900, "Контур и форма", "beard,style"),
            ("Камуфляж седины", 1500, "Тонирование под натуральный цвет", "hair,men"),
        ],
        "autoservice": [
            ("Замена масла", 2200, "Работа + расходники по согласованию", "car,service"),
            ("Диагностика ходовой", 1800, "Проверка подвески и рекомендация", "auto,mechanic"),
            ("Замена тормозных колодок", 3200, "Передняя ось", "brake,car"),
            ("Компьютерная диагностика", 1500, "Считывание ошибок ЭБУ", "auto,diagnostic"),
        ],
        "evacuator": [
            ("Эвакуация по городу", 3500, "Подача до 30 минут", "tow,truck"),
            ("Эвакуация за город", 5500, "До 30 км от города", "evacuator,car"),
            ("Перевозка авто после ДТП", 4800, "Бережная погрузка", "accident,car"),
            ("Прикурить авто", 1200, "Выезд и запуск АКБ", "battery,car"),
        ],
        "food": [
            ("Бизнес-ланч", 450, "Суп, второе и салат", "food,lunch"),
            ("Пицца Пепперони 30см", 790, "Классическая пицца", "pizza,food"),
            ("Сет роллов", 1290, "24 кусочка", "sushi,roll"),
            ("Бургер фирменный", 520, "Сочная котлета и соус", "burger,food"),
        ],
    }
    base = catalog[niche]
    result: list[dict[str, str]] = []
    for i in range(count):
        name, price, desc, query = base[i % len(base)]
        result.append(
            {
                "name": f"{name} #{i+1}",
                "price": str(price + (i // len(base)) * 50),
                "description": desc,
                "image": f"https://source.unsplash.com/1200x800/?{query}&sig={i+1}",
            }
        )
    return result


def human_pause(enabled: bool, min_s: float = 0.25, max_s: float = 0.8) -> None:
    if enabled:
        sleep(random.uniform(min_s, max_s))


def clear_and_type(page: Page, selector: str, value: str, human: bool) -> None:
    page.click(selector)
    page.keyboard.press("Control+a")
    page.keyboard.press("Backspace")
    for ch in value:
        page.keyboard.type(ch, delay=35 if human else 0)
    human_pause(human, 0.08, 0.2)


def login(page: Page, base_url: str, email: str, password: str, human: bool) -> None:
    page.goto(f"{base_url}/login", wait_until="domcontentloaded", timeout=60000)
    clear_and_type(page, 'input[type="email"]', email, human)
    clear_and_type(page, 'input[type="password"]', password, human)
    page.click('button[type="submit"]')
    page.wait_for_timeout(2500)


def ensure_shop(page: Page, base_url: str, shop: DemoShop, human: bool) -> None:
    page.goto(f"{base_url}/create-shop", wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(1200)
    if page.locator("#name").count() == 0:
        return
    clear_and_type(page, "#name", shop.name, human)
    clear_and_type(page, "#bot_token", shop.bot_token, human)
    clear_and_type(page, "#delivery_name", shop.delivery_name, human)
    clear_and_type(page, "#delivery_price", shop.delivery_price, human)
    clear_and_type(page, "#notification_username", shop.bot_username, human)
    page.click('button:has-text("Создать магазин")')
    page.wait_for_timeout(2200)


def open_shop_products(page: Page, base_url: str, shop_name: str) -> bool:
    page.goto(f"{base_url}/shops", wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(1400)
    shop_card = page.locator(".shop-card", has_text=shop_name).first
    if shop_card.count() == 0:
        return False
    products_link = shop_card.locator('a:has-text("Товары")').first
    if products_link.count() == 0:
        return False
    href = products_link.get_attribute("href") or ""
    if not href:
        return False
    page.goto(f"{base_url}{href}", wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(1500)
    return True


def add_product_ui(page: Page, item: dict[str, str], human: bool) -> bool:
    add_btn = page.locator('button:has-text("Добавить товар")').first
    if add_btn.count() == 0:
        return False
    add_btn.click()
    page.wait_for_timeout(450)
    modal = page.locator(".modal .modal-content").first
    if modal.count() == 0:
        return False
    clear_and_type(modal, '.form-group input:not([type="number"]):not([type="file"])', item["name"], human)
    clear_and_type(modal, '.form-group input[type="number"]', item["price"], human)
    clear_and_type(modal, ".form-group textarea", item["description"], human)
    clear_and_type(modal, 'input[placeholder="https://..."]', item["image"], human)
    modal.locator('button[type="submit"]:has-text("Сохранить")').first.click()
    page.wait_for_timeout(1200)
    return True


def main() -> int:
    args = parse_args()
    with sync_playwright() as p:
        launch = {"headless": args.headless}
        if args.human:
            launch["slow_mo"] = args.slow_ms
        if args.browser == "chrome":
            launch["channel"] = "chrome"
        browser = p.chromium.launch(**launch)
        page = browser.new_page()

        login(page, args.base_url, args.email, args.password, args.human)

        for shop in DEMO_SHOPS:
            ensure_shop(page, args.base_url, shop, args.human)
            if not open_shop_products(page, args.base_url, shop.name):
                continue
            products = products_for_niche(shop.niche, args.products_per_shop)
            for i, item in enumerate(products):
                ok = add_product_ui(page, item, args.human)
                if not ok:
                    break
                human_pause(args.human, 0.35, 0.95)
                if (i + 1) % 5 == 0:
                    page.wait_for_timeout(1200)

        page.wait_for_timeout(2000)
        browser.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

