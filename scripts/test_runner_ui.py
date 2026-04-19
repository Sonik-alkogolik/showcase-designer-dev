from __future__ import annotations

import subprocess
import sys
import threading
from dataclasses import dataclass
from pathlib import Path
from queue import Empty, Queue
import shutil
import tkinter as tk
from tkinter import ttk, messagebox
from tkinter.scrolledtext import ScrolledText


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


@dataclass
class TestSpec:
    key: str
    title: str
    description: str
    builder: callable


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


def _cmd_full_step(stop_after: str):
    def _builder(ctx: RunnerContext) -> list[str]:
        cmd = _cmd_full(ctx)
        cmd.extend(["--stop-after", stop_after])
        return cmd

    return _builder


def _cmd_prod_smoke(_: RunnerContext) -> list[str]:
    return [sys.executable, "tools/run_prod_smoke.py"]


TESTS: list[TestSpec] = [
    TestSpec(
        key="auth",
        title="Тест регистрации/входа (auth)",
        description="Проверка входа и профиля через browser e2e_auth_login.py",
        builder=_cmd_auth,
    ),
    TestSpec(
        key="shop",
        title="Тест создания магазина",
        description="Логин -> создание магазина -> первичная проверка",
        builder=_cmd_create_shop,
    ),
    TestSpec(
        key="products",
        title="Тест товаров/категорий",
        description="Проверка карточек, категории, ручного добавления",
        builder=_cmd_products,
    ),
    TestSpec(
        key="full",
        title="Полный e2e-флоу",
        description="Регистрация -> Telegram -> тариф -> магазин -> товар -> импорт -> удаление",
        builder=_cmd_full,
    ),
    TestSpec(
        key="smoke",
        title="Prod smoke (pytest)",
        description="Безопасный smoke-набор из tools/autotests",
        builder=_cmd_prod_smoke,
    ),
]

FULL_FLOW_STEPS: list[TestSpec] = [
    TestSpec(
        key="step_register",
        title="Шаг 1: Регистрация",
        description="Регистрация нового пользователя",
        builder=_cmd_full_step("register"),
    ),
    TestSpec(
        key="step_subscribe",
        title="Шаг 2: Тариф",
        description="Выбор/активация тарифа",
        builder=_cmd_full_step("subscribe"),
    ),
    TestSpec(
        key="step_telegram",
        title="Шаг 3: Telegram link",
        description="Привязка Telegram",
        builder=_cmd_full_step("telegram"),
    ),
    TestSpec(
        key="step_shop",
        title="Шаг 4: Магазин",
        description="Создание магазина",
        builder=_cmd_full_step("shop"),
    ),
    TestSpec(
        key="step_connect_bot",
        title="Шаг 5: Подключить бота",
        description="Проверка подключения бота в настройках",
        builder=_cmd_full_step("connect_bot"),
    ),
    TestSpec(
        key="step_add_product",
        title="Шаг 6: Добавить товар",
        description="Ручное создание товара/категории",
        builder=_cmd_full_step("add_product"),
    ),
    TestSpec(
        key="step_import",
        title="Шаг 7: Импорт",
        description="Импорт товара из CSV",
        builder=_cmd_full_step("import"),
    ),
    TestSpec(
        key="step_delete",
        title="Шаг 8: Удалить аккаунт",
        description="Удаление аккаунта и проверка cleanup",
        builder=_cmd_full_step("delete"),
    ),
]


class TestRunnerApp(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.title("Showcase Test Runner")
        self.geometry("1280x760")
        self.minsize(1080, 640)
        self.protocol("WM_DELETE_WINDOW", self._on_close)

        self.proc: subprocess.Popen[str] | None = None
        self.browser_proc: subprocess.Popen[str] | None = None
        self.queue: Queue[str] = Queue()
        self.selected: TestSpec | None = None

        self._build_ui()
        self._select_test(TESTS[0])
        self.after(100, self._flush_logs)

    def _build_ui(self) -> None:
        self.columnconfigure(1, weight=1)
        self.rowconfigure(0, weight=1)

        sidebar = ttk.Frame(self, padding=10)
        sidebar.grid(row=0, column=0, sticky="nsw")
        sidebar.rowconfigure(1, weight=1)

        ttk.Label(sidebar, text="Тесты", font=("Segoe UI", 12, "bold")).grid(row=0, column=0, sticky="w")

        list_wrap = ttk.Frame(sidebar)
        list_wrap.grid(row=1, column=0, sticky="nsw", pady=(8, 0))

        self.tests_list = tk.Listbox(list_wrap, width=38, height=28, activestyle="dotbox")
        self.tests_list.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        y_scroll = ttk.Scrollbar(list_wrap, orient=tk.VERTICAL, command=self.tests_list.yview)
        y_scroll.pack(side=tk.RIGHT, fill=tk.Y)
        self.tests_list.configure(yscrollcommand=y_scroll.set)

        for spec in TESTS:
            self.tests_list.insert(tk.END, f"• {spec.title}")
        self.tests_list.bind("<<ListboxSelect>>", self._on_select)

        steps_wrap = ttk.LabelFrame(sidebar, text="Шаги Full E2E", padding=8)
        steps_wrap.grid(row=2, column=0, sticky="ew", pady=(10, 0))
        for idx, spec in enumerate(FULL_FLOW_STEPS):
            btn = ttk.Button(
                steps_wrap,
                text=spec.title,
                command=lambda s=spec: self._run_step(s),
            )
            btn.grid(row=idx, column=0, sticky="ew", pady=(0, 5))

        right = ttk.Frame(self, padding=12)
        right.grid(row=0, column=1, sticky="nsew")
        right.columnconfigure(0, weight=1)
        right.rowconfigure(3, weight=1)

        self.title_var = tk.StringVar()
        self.desc_var = tk.StringVar()
        ttk.Label(right, textvariable=self.title_var, font=("Segoe UI", 13, "bold")).grid(row=0, column=0, sticky="w")
        ttk.Label(right, textvariable=self.desc_var, foreground="#4b5563").grid(row=1, column=0, sticky="w", pady=(4, 10))

        params = ttk.LabelFrame(right, text="Параметры", padding=10)
        params.grid(row=2, column=0, sticky="ew")
        for i in range(4):
            params.columnconfigure(i, weight=1)

        self.base_url = tk.StringVar(value="https://e-tgo.ru")
        self.email = tk.StringVar(value="test@example.com")
        self.password = tk.StringVar(value="password")
        self.chat_id = tk.StringVar(value="954773719")
        self.bot_token = tk.StringVar(value="")
        self.shop_id = tk.StringVar(value="2")
        self.browser = tk.StringVar(value="chrome")
        self.plan = tk.StringVar(value="business")
        self.headless = tk.BooleanVar(value=False)
        self.human = tk.BooleanVar(value=True)
        self.keep_user = tk.BooleanVar(value=True)
        self.keep_open = tk.BooleanVar(value=False)
        self.manual_telegram = tk.BooleanVar(value=False)

        self._field(params, "Base URL", self.base_url, 0, 0)
        self._field(params, "Email", self.email, 0, 2)
        self._field(params, "Password", self.password, 1, 0, show="*")
        self._field(params, "Chat ID", self.chat_id, 1, 2)
        self._field(params, "Bot Token", self.bot_token, 2, 0)
        self._field(params, "Shop ID", self.shop_id, 2, 2)

        ttk.Label(params, text="Browser").grid(row=3, column=0, sticky="w", pady=(8, 0))
        ttk.Combobox(params, textvariable=self.browser, state="readonly", values=["chrome", "chromium"]).grid(
            row=3, column=1, sticky="ew", padx=(8, 16), pady=(8, 0)
        )
        ttk.Label(params, text="Plan").grid(row=3, column=2, sticky="w", pady=(8, 0))
        ttk.Combobox(params, textvariable=self.plan, state="readonly", values=["business", "starter"]).grid(
            row=3, column=3, sticky="ew", padx=(8, 0), pady=(8, 0)
        )

        flags = ttk.Frame(params)
        flags.grid(row=4, column=0, columnspan=4, sticky="w", pady=(10, 0))
        ttk.Checkbutton(flags, text="Headless", variable=self.headless).pack(side=tk.LEFT, padx=(0, 10))
        ttk.Checkbutton(flags, text="Human mode", variable=self.human).pack(side=tk.LEFT, padx=(0, 10))
        ttk.Checkbutton(flags, text="Не удалять пользователя", variable=self.keep_user).pack(side=tk.LEFT, padx=(0, 10))
        ttk.Checkbutton(flags, text="Оставить браузер открытым", variable=self.keep_open).pack(side=tk.LEFT, padx=(0, 10))
        ttk.Checkbutton(flags, text="Ручной шаг Telegram", variable=self.manual_telegram).pack(side=tk.LEFT)

        actions = ttk.Frame(right)
        actions.grid(row=3, column=0, sticky="ew", pady=(12, 8))
        self.run_btn = ttk.Button(actions, text="Запуск выбранного теста", command=self._run_selected)
        self.run_btn.pack(side=tk.LEFT)
        self.stop_btn = ttk.Button(actions, text="Остановить", command=self._stop_process, state=tk.DISABLED)
        self.stop_btn.pack(side=tk.LEFT, padx=(8, 0))
        self.open_browser_btn = ttk.Button(actions, text="Открыть браузер", command=self._open_manual_browser)
        self.open_browser_btn.pack(side=tk.LEFT, padx=(8, 0))
        self.close_browser_btn = ttk.Button(
            actions,
            text="Закрыть браузер",
            command=self._close_manual_browser,
            state=tk.DISABLED,
        )
        self.close_browser_btn.pack(side=tk.LEFT, padx=(8, 0))
        ttk.Button(actions, text="Очистить лог", command=self._clear_log).pack(side=tk.LEFT, padx=(8, 0))

        self.cmd_var = tk.StringVar(value="")
        ttk.Label(right, text="Команда:").grid(row=4, column=0, sticky="w")
        ttk.Entry(right, textvariable=self.cmd_var, state="readonly").grid(row=5, column=0, sticky="ew", pady=(2, 8))

        self.log = ScrolledText(right, wrap=tk.WORD, font=("Consolas", 10), height=16)
        self.log.grid(row=6, column=0, sticky="nsew")
        right.rowconfigure(6, weight=1)
        self.log.insert(tk.END, "Готово к запуску тестов.\n")

    @staticmethod
    def _field(parent: ttk.Frame, label: str, variable: tk.StringVar, row: int, col: int, show: str | None = None) -> None:
        ttk.Label(parent, text=label).grid(row=row, column=col, sticky="w", pady=(6, 0))
        entry = ttk.Entry(parent, textvariable=variable, show=show or "")
        entry.grid(row=row, column=col + 1, sticky="ew", padx=(8, 16), pady=(6, 0))

    def _build_context(self) -> RunnerContext:
        return RunnerContext(
            base_url=self.base_url.get().strip(),
            email=self.email.get().strip(),
            password=self.password.get(),
            chat_id=self.chat_id.get().strip(),
            bot_token=self.bot_token.get().strip(),
            shop_id=self.shop_id.get().strip(),
            browser=self.browser.get().strip(),
            plan=self.plan.get().strip(),
            headless=self.headless.get(),
            human=self.human.get(),
            keep_user=self.keep_user.get(),
            keep_open=self.keep_open.get(),
            manual_telegram=self.manual_telegram.get(),
        )

    def _on_select(self, _: object) -> None:
        index = self.tests_list.curselection()
        if not index:
            return
        self._select_test(TESTS[index[0]])

    def _select_test(self, spec: TestSpec) -> None:
        self.selected = spec
        self.title_var.set(spec.title)
        self.desc_var.set(spec.description)
        try:
            cmd = spec.builder(self._build_context())
            self.cmd_var.set(" ".join(cmd))
        except Exception:
            self.cmd_var.set("")

    def _run_selected(self) -> None:
        if self.proc is not None:
            messagebox.showwarning("Запуск", "Тест уже выполняется. Остановите текущий процесс.")
            return
        if self.selected is None:
            return

        ctx = self._build_context()
        cmd = self.selected.builder(ctx)
        self.cmd_var.set(" ".join(cmd))
        self._append_log(f"\n=== START: {self.selected.title} ===\n$ {' '.join(cmd)}\n")

        self.run_btn.configure(state=tk.DISABLED)
        self.stop_btn.configure(state=tk.NORMAL)

        def worker() -> None:
            try:
                self.proc = subprocess.Popen(
                    cmd,
                    cwd=ROOT,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.STDOUT,
                    text=True,
                    encoding="utf-8",
                    errors="replace",
                )
                assert self.proc.stdout is not None
                for line in self.proc.stdout:
                    self.queue.put(line)
                code = self.proc.wait()
                self.queue.put(f"\n=== FINISH (exit {code}) ===\n")
            except Exception as exc:
                self.queue.put(f"\n[runner error] {exc}\n")
            finally:
                self.proc = None
                self.queue.put("__STATE_IDLE__")

        threading.Thread(target=worker, daemon=True).start()

    def _run_step(self, step_spec: TestSpec) -> None:
        self._select_test(step_spec)
        self._run_selected()

    def _stop_process(self) -> None:
        if self.proc is None:
            return
        try:
            self.proc.terminate()
            self._append_log("\n[manual stop] Sent terminate signal.\n")
        except Exception as exc:
            self._append_log(f"\n[manual stop error] {exc}\n")

    def _chrome_binary(self) -> str | None:
        candidates = [
            shutil.which("chrome"),
            r"C:\Program Files\Google\Chrome\Application\chrome.exe",
            r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
        ]
        for path in candidates:
            if path and Path(path).exists():
                return path
        return None

    def _open_manual_browser(self) -> None:
        if self.browser_proc is not None and self.browser_proc.poll() is None:
            self._append_log("[browser] Уже открыт.\n")
            return

        browser_bin = self._chrome_binary()
        if not browser_bin:
            messagebox.showerror("Браузер", "Chrome не найден. Установите Chrome или укажите путь в коде.")
            return

        url = self.base_url.get().strip() or "https://e-tgo.ru"
        try:
            self.browser_proc = subprocess.Popen([browser_bin, "--new-window", url], cwd=ROOT)
            self.close_browser_btn.configure(state=tk.NORMAL)
            self._append_log(f"[browser] Открыт: {url}\n")
        except Exception as exc:
            messagebox.showerror("Браузер", f"Не удалось открыть браузер: {exc}")

    def _close_manual_browser(self) -> None:
        proc = self.browser_proc
        if proc is None:
            return
        try:
            if proc.poll() is None:
                proc.terminate()
                try:
                    proc.wait(timeout=4)
                except subprocess.TimeoutExpired:
                    proc.kill()
            self._append_log("[browser] Закрыт.\n")
        except Exception as exc:
            self._append_log(f"[browser] Ошибка закрытия: {exc}\n")
        finally:
            self.browser_proc = None
            self.close_browser_btn.configure(state=tk.DISABLED)

    def _clear_log(self) -> None:
        self.log.delete("1.0", tk.END)

    def _append_log(self, text: str) -> None:
        self.log.insert(tk.END, text)
        self.log.see(tk.END)

    def _flush_logs(self) -> None:
        try:
            while True:
                msg = self.queue.get_nowait()
                if msg == "__STATE_IDLE__":
                    self.run_btn.configure(state=tk.NORMAL)
                    self.stop_btn.configure(state=tk.DISABLED)
                    if self.selected:
                        self._select_test(self.selected)
                else:
                    self._append_log(msg)
        except Empty:
            pass
        self.after(120, self._flush_logs)

    def _on_close(self) -> None:
        self._close_manual_browser()
        self.destroy()


def main() -> int:
    app = TestRunnerApp()
    app.mainloop()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
