from __future__ import annotations

import shutil
import subprocess
import sys
from dataclasses import dataclass
from pathlib import Path

import gradio as gr

ROOT = Path(__file__).resolve().parents[1]


@dataclass
class RunnerContext:
    base_url: str
    email: str
    password: str
    chat_id: str
    bot_token: str
    shop_id: str
    browser: str
    plan: str
    headless: bool
    human: bool
    keep_user: bool
    keep_open: bool
    manual_telegram: bool


CURRENT_PROC: subprocess.Popen[str] | None = None
BROWSER_PROC: subprocess.Popen[str] | None = None


def _cmd_full(ctx: RunnerContext) -> list[str]:
    cmd = [
        sys.executable,
        "tools/e2e_prod_real_user_full.py",
        "--base-url",
        ctx.base_url,
        "--browser",
        ctx.browser,
        "--plan",
        ctx.plan,
        "--chat-id",
        ctx.chat_id,
        "--bot-token",
        ctx.bot_token,
    ]
    if ctx.headless:
        cmd.append("--headless")
    if ctx.human:
        cmd.append("--human")
    if ctx.keep_user:
        cmd.append("--skip-delete")
    if ctx.keep_open:
        cmd.append("--keep-open")
    if ctx.manual_telegram:
        cmd.append("--manual-telegram-link")
    return cmd


def _cmd_full_step(ctx: RunnerContext, stop_after: str) -> list[str]:
    cmd = _cmd_full(ctx)
    cmd.extend(["--stop-after", stop_after])
    return cmd


def _cmd_auth(ctx: RunnerContext) -> list[str]:
    cmd = [
        sys.executable,
        "tools/e2e_auth_login.py",
        "--base-url",
        ctx.base_url,
        "--email",
        ctx.email,
        "--password",
        ctx.password,
        "--browser",
        ctx.browser,
    ]
    if ctx.headless:
        cmd.append("--headless")
    if ctx.human:
        cmd.append("--human")
    if ctx.keep_open:
        cmd.append("--keep-open")
    return cmd


def _cmd_create_shop(ctx: RunnerContext) -> list[str]:
    cmd = [
        sys.executable,
        "tools/e2e_create_shop.py",
        "--base-url",
        ctx.base_url,
        "--email",
        ctx.email,
        "--password",
        ctx.password,
        "--chat-id",
        ctx.chat_id,
        "--browser",
        ctx.browser,
    ]
    if ctx.headless:
        cmd.append("--headless")
    if ctx.human:
        cmd.append("--human")
    if ctx.keep_open:
        cmd.append("--keep-open")
    return cmd


def _cmd_products(ctx: RunnerContext) -> list[str]:
    cmd = [
        sys.executable,
        "tools/e2e_shop_products_flow.py",
        "--base-url",
        ctx.base_url,
        "--email",
        ctx.email,
        "--password",
        ctx.password,
        "--shop-id",
        ctx.shop_id,
        "--browser",
        ctx.browser,
    ]
    if ctx.headless:
        cmd.append("--headless")
    if ctx.human:
        cmd.append("--human")
    if ctx.keep_open:
        cmd.append("--keep-open")
    return cmd


def _ctx_from_inputs(base_url, email, password, chat_id, bot_token, shop_id, browser, plan, headless, human, keep_user, keep_open, manual_telegram):
    return RunnerContext(
        base_url=(base_url or "").strip(),
        email=(email or "").strip(),
        password=password or "",
        chat_id=(chat_id or "").strip(),
        bot_token=(bot_token or "").strip(),
        shop_id=(shop_id or "").strip(),
        browser=(browser or "chrome").strip(),
        plan=(plan or "business").strip(),
        headless=bool(headless),
        human=bool(human),
        keep_user=bool(keep_user),
        keep_open=bool(keep_open),
        manual_telegram=bool(manual_telegram),
    )


def _run_command(cmd: list[str]) -> tuple[str, str]:
    global CURRENT_PROC
    if CURRENT_PROC is not None and CURRENT_PROC.poll() is None:
        return "Процесс уже выполняется", "Остановите текущий запуск перед новым."

    try:
        CURRENT_PROC = subprocess.Popen(
            cmd,
            cwd=ROOT,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            encoding="utf-8",
            errors="replace",
        )
        out, _ = CURRENT_PROC.communicate()
        code = CURRENT_PROC.returncode
        status = f"Готово (exit {code})"
        return status, f"$ {' '.join(cmd)}\n\n{out}"
    except Exception as exc:
        return "Ошибка запуска", str(exc)
    finally:
        CURRENT_PROC = None


def run_named(kind, *vals):
    ctx = _ctx_from_inputs(*vals)
    if kind == "auth":
        return _run_command(_cmd_auth(ctx))
    if kind == "shop":
        return _run_command(_cmd_create_shop(ctx))
    if kind == "products":
        return _run_command(_cmd_products(ctx))
    if kind == "full":
        return _run_command(_cmd_full(ctx))
    if kind.startswith("step:"):
        return _run_command(_cmd_full_step(ctx, kind.split(":", 1)[1]))
    return "Неизвестная команда", kind


def stop_run():
    global CURRENT_PROC
    if CURRENT_PROC is None or CURRENT_PROC.poll() is not None:
        return "Нет активного процесса", ""
    try:
        CURRENT_PROC.terminate()
        return "Остановлено", "Отправлен terminate текущему процессу"
    except Exception as exc:
        return "Ошибка остановки", str(exc)


def _find_chrome() -> str | None:
    for p in [
        shutil.which("chrome"),
        r"C:\Program Files\Google\Chrome\Application\chrome.exe",
        r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    ]:
        if p and Path(p).exists():
            return p
    return None


def open_browser(base_url):
    global BROWSER_PROC
    if BROWSER_PROC is not None and BROWSER_PROC.poll() is None:
        return "Браузер уже открыт", ""
    chrome = _find_chrome()
    if not chrome:
        return "Chrome не найден", ""
    url = (base_url or "https://e-tgo.ru").strip()
    try:
        BROWSER_PROC = subprocess.Popen([chrome, "--new-window", url], cwd=ROOT)
        return f"Открыт браузер: {url}", ""
    except Exception as exc:
        return "Ошибка", str(exc)


def close_browser():
    global BROWSER_PROC
    if BROWSER_PROC is None:
        return "Браузер не запущен", ""
    try:
        if BROWSER_PROC.poll() is None:
            BROWSER_PROC.terminate()
        BROWSER_PROC = None
        return "Браузер закрыт", ""
    except Exception as exc:
        return "Ошибка", str(exc)


with gr.Blocks(title="Showcase Test Runner (Web)") as demo:
    gr.Markdown("## Showcase Test Runner (Web Sidebar)")
    with gr.Row():
        with gr.Column(scale=1):
            gr.Markdown("### Основные тесты")
            b_auth = gr.Button("Тест регистрации/входа")
            b_shop = gr.Button("Тест создания магазина")
            b_products = gr.Button("Тест товаров")
            b_full = gr.Button("Полный e2e-флоу")

            gr.Markdown("### Шаги Full E2E")
            s_register = gr.Button("Шаг 1: Регистрация")
            s_subscribe = gr.Button("Шаг 2: Тариф")
            s_telegram = gr.Button("Шаг 3: Telegram")
            s_shop = gr.Button("Шаг 4: Магазин")
            s_connect = gr.Button("Шаг 5: Подключить бота")
            s_add = gr.Button("Шаг 6: Добавить товар")
            s_import = gr.Button("Шаг 7: Импорт")
            s_delete = gr.Button("Шаг 8: Удалить аккаунт")

            gr.Markdown("### Браузер")
            b_open = gr.Button("Открыть браузер")
            b_close = gr.Button("Закрыть браузер")
            b_stop = gr.Button("Остановить текущий запуск")

        with gr.Column(scale=2):
            base_url = gr.Textbox(label="Base URL", value="https://e-tgo.ru")
            email = gr.Textbox(label="Email", value="test@example.com")
            password = gr.Textbox(label="Password", value="password", type="password")
            chat_id = gr.Textbox(label="Chat ID", value="954773719")
            bot_token = gr.Textbox(label="Bot Token", value="")
            shop_id = gr.Textbox(label="Shop ID", value="2")
            browser = gr.Dropdown(label="Browser", choices=["chrome", "chromium"], value="chrome")
            plan = gr.Dropdown(label="Plan", choices=["business", "starter"], value="business")
            headless = gr.Checkbox(label="Headless", value=False)
            human = gr.Checkbox(label="Human mode", value=True)
            keep_user = gr.Checkbox(label="Не удалять пользователя", value=True)
            keep_open = gr.Checkbox(label="Оставить браузер открытым", value=False)
            manual_telegram = gr.Checkbox(label="Ручной шаг Telegram", value=False)

            status = gr.Textbox(label="Статус")
            logs = gr.Textbox(label="Логи", lines=22)

    all_inputs = [base_url, email, password, chat_id, bot_token, shop_id, browser, plan, headless, human, keep_user, keep_open, manual_telegram]

    b_auth.click(lambda *x: run_named("auth", *x), inputs=all_inputs, outputs=[status, logs])
    b_shop.click(lambda *x: run_named("shop", *x), inputs=all_inputs, outputs=[status, logs])
    b_products.click(lambda *x: run_named("products", *x), inputs=all_inputs, outputs=[status, logs])
    b_full.click(lambda *x: run_named("full", *x), inputs=all_inputs, outputs=[status, logs])

    s_register.click(lambda *x: run_named("step:register", *x), inputs=all_inputs, outputs=[status, logs])
    s_subscribe.click(lambda *x: run_named("step:subscribe", *x), inputs=all_inputs, outputs=[status, logs])
    s_telegram.click(lambda *x: run_named("step:telegram", *x), inputs=all_inputs, outputs=[status, logs])
    s_shop.click(lambda *x: run_named("step:shop", *x), inputs=all_inputs, outputs=[status, logs])
    s_connect.click(lambda *x: run_named("step:connect_bot", *x), inputs=all_inputs, outputs=[status, logs])
    s_add.click(lambda *x: run_named("step:add_product", *x), inputs=all_inputs, outputs=[status, logs])
    s_import.click(lambda *x: run_named("step:import", *x), inputs=all_inputs, outputs=[status, logs])
    s_delete.click(lambda *x: run_named("step:delete", *x), inputs=all_inputs, outputs=[status, logs])

    b_open.click(open_browser, inputs=[base_url], outputs=[status, logs])
    b_close.click(close_browser, outputs=[status, logs])
    b_stop.click(stop_run, outputs=[status, logs])


if __name__ == "__main__":
    demo.launch(server_name="127.0.0.1", server_port=7861, inbrowser=True)
