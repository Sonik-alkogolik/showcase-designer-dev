from __future__ import annotations

import json
import subprocess
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen


HOST = "127.0.0.1"
PORT = 8787
ROOT = Path(__file__).resolve().parents[1]
SHORTCUTS = ROOT / "scripts" / "dev-shortcuts.ps1"


HTML = r"""<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Showcase Dev UI</title>
  <style>
    body { font-family: Segoe UI, Arial, sans-serif; margin: 24px; background: #f7f8fb; color: #1b1f2a; }
    .card { background: #fff; border: 1px solid #e2e6ef; border-radius: 12px; padding: 16px; margin-bottom: 16px; max-width: 920px; }
    h1 { margin: 0 0 12px; font-size: 24px; }
    h2 { margin: 0 0 8px; font-size: 18px; }
    .row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
    button { border: 0; border-radius: 8px; background: #1f6feb; color: #fff; padding: 8px 12px; cursor: pointer; }
    button.secondary { background: #4b5563; }
    input { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; min-width: 260px; }
    pre { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 12px; overflow: auto; min-height: 80px; }
    .hint { color: #475569; font-size: 14px; margin-top: 6px; }
    .cmd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px; }
    .cmd-tile { border: 1px solid #dbe1ef; border-radius: 10px; padding: 10px; background: #fbfcff; }
    .cmd-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 6px; }
    .cmd-title { font-size: 13px; font-weight: 600; color: #334155; }
    .cmd-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
    .cmd-plan { display: inline-block; font-size: 11px; color: #0f766e; background: #ccfbf1; border: 1px solid #99f6e4; border-radius: 999px; padding: 2px 8px; margin-top: 6px; }
    .cmd-copy { font-size: 12px; padding: 4px 8px; background: #0ea5e9; }
    .cmd-code { margin: 0; min-height: 0; padding: 10px; font-size: 12px; line-height: 1.35; }
  </style>
</head>
<body>
  <div class="card">
    <h1>showcase-designer-dev: quick UI</h1>
    <div class="row">
      <button onclick="runAction('open-project')">Open Project</button>
      <button onclick="runAction('open-browser')">Open App</button>
      <button onclick="runAction('open-login')">Open Login</button>
      <button onclick="runAction('db-ping')">DB Ping</button>
      <button class="secondary" onclick="runAction('help')">Help</button>
    </div>
    <div class="hint">Кнопки запускают команды из scripts/dev-shortcuts.ps1.</div>
  </div>

  <div class="card">
    <h2>Task-Driven Command Tiles (newest first)</h2>
    <div class="hint">Плитки рендерятся в обратном порядке: новые команды сверху. Добавляй новые элементы в массив <code>taskTiles</code> внизу файла.</div>
    <div id="taskTiles" class="cmd-grid"></div>
  </div>

  <div class="card">
    <h2>API Login</h2>
    <div class="row">
      <input id="email" value="test@example.com" placeholder="Email">
      <input id="password" value="password" placeholder="Password" type="password">
      <button onclick="apiLogin()">Run API Login</button>
    </div>
    <div class="hint">Запрос: POST /api/login на локальный backend.</div>
  </div>

  <div class="card">
    <h2>HTTP Test Lab</h2>
    <div class="row">
      <button onclick="preset('cors')">Preset: test-cors</button>
      <button onclick="preset('login')">Preset: login</button>
      <button onclick="preset('profile')">Preset: profile</button>
      <button class="secondary" onclick="runHttp()">Send Request</button>
    </div>
    <div class="row">
      <input id="httpMethod" value="GET" placeholder="Method (GET/POST/PUT/DELETE)">
      <input id="httpUrl" value="http://127.0.0.1:8000/api/test-cors" placeholder="URL">
    </div>
    <div class="row">
      <input id="httpToken" placeholder="Bearer token (optional)">
    </div>
    <div class="row">
      <input id="httpBody" value="{}" placeholder='JSON body, e.g. {"email":"test@gmail.com","password":"11111111"}'>
    </div>
    <div class="hint">Для profile: сначала выполни login, вставь token в Bearer token и отправь GET /api/profile.</div>
  </div>

  <div class="card">
    <h2>Output</h2>
    <pre id="out">Ready.</pre>
  </div>

  <script>
    const taskTiles = [
      {
        title: 'Set Project Path',
        sub: 'Перейти в папку проекта',
        plan: 'Core Setup',
        cmd: 'Set-Location C:\\Users\\admin\\Desktop\\myproject\\showcase-designer'
      },
      {
        title: 'Shortcuts Help',
        sub: 'Показать список быстрых команд',
        plan: 'Core Setup',
        cmd: '.\\scripts\\dev-shortcuts.ps1 help'
      },
      {
        title: 'Open Project (VS Code)',
        sub: 'Открыть проект в VS Code',
        plan: 'Dev UX',
        cmd: '.\\scripts\\dev-shortcuts.ps1 open-project'
      },
      {
        title: 'Open App',
        sub: 'Открыть приложение в браузере',
        plan: 'Dev UX',
        cmd: '.\\scripts\\dev-shortcuts.ps1 open-browser'
      },
      {
        title: 'Open Login',
        sub: 'Открыть страницу входа',
        plan: 'Auth',
        cmd: '.\\scripts\\dev-shortcuts.ps1 open-login'
      },
      {
        title: 'DB Ping',
        sub: 'Проверить подключение к базе',
        plan: 'Infra / DB',
        cmd: '.\\scripts\\dev-shortcuts.ps1 db-ping'
      },
      {
        title: 'API Login (Custom)',
        sub: 'Проверить вход с вашими данными',
        plan: 'Auth API',
        cmd: '.\\scripts\\dev-shortcuts.ps1 api-login -Email "test@gmail.com" -Password "11111111"'
      },
      {
        title: 'Direct Login (curl)',
        sub: 'Прямой POST /api/login через curl',
        plan: 'Auth API',
        cmd: 'curl.exe -s -i -H "Content-Type: application/json" --data "{\\"email\\":\\"test@gmail.com\\",\\"password\\":\\"11111111\\"}" "http://127.0.0.1:8000/api/login"'
      },
      {
        title: 'Profile by Token (curl)',
        sub: 'Получить профиль по Bearer токену',
        plan: 'Auth API',
        cmd: 'curl.exe -s -i -H "Authorization: Bearer <TOKEN>" "http://127.0.0.1:8000/api/profile"'
      },
      {
        title: 'CORS Test (curl)',
        sub: 'Проверить тестовый CORS endpoint',
        plan: 'API Smoke',
        cmd: 'curl.exe -s -i "http://127.0.0.1:8000/api/test-cors"'
      }
    ];

    function renderTaskTiles() {
      const grid = document.getElementById('taskTiles');
      grid.innerHTML = '';

      [...taskTiles].reverse().forEach((tile) => {
        const wrap = document.createElement('div');
        wrap.className = 'cmd-tile';

        const head = document.createElement('div');
        head.className = 'cmd-head';

        const left = document.createElement('div');
        const title = document.createElement('div');
        title.className = 'cmd-title';
        title.textContent = tile.title;

        const sub = document.createElement('div');
        sub.className = 'cmd-sub';
        sub.textContent = tile.sub;

        const plan = document.createElement('span');
        plan.className = 'cmd-plan';
        plan.textContent = tile.plan;

        left.appendChild(title);
        left.appendChild(sub);
        left.appendChild(plan);

        const btn = document.createElement('button');
        btn.className = 'cmd-copy';
        btn.textContent = 'Run';
        btn.addEventListener('click', () => copyCommand(btn, tile.cmd));

        head.appendChild(left);
        head.appendChild(btn);

        const code = document.createElement('pre');
        code.className = 'cmd-code';
        code.textContent = tile.cmd;

        wrap.appendChild(head);
        wrap.appendChild(code);
        grid.appendChild(wrap);
      });
    }

    async function copyCommand(btn, text) {
      const out = document.getElementById('out');
      const cmd = (text || '').trim();
      out.textContent = 'Running tile command ...';

      try {
        if (cmd.toLowerCase().startsWith('set-location ')) {
          out.textContent = 'Set-Location меняет директорию только в локальном терминале. Выполни эту команду вручную в PowerShell.';
          return;
        }

        if (cmd.startsWith('.\\scripts\\dev-shortcuts.ps1')) {
          const m = cmd.match(/^\.\\scripts\\dev-shortcuts\.ps1\s+([a-z-]+)(.*)$/i);
          if (!m) {
            out.textContent = 'Не удалось распознать shortcut-команду.';
            return;
          }

          const action = m[1];
          if (action === 'db-shell') {
            out.textContent = 'db-shell интерактивная команда. Запусти её в терминале: .\\scripts\\dev-shortcuts.ps1 db-shell';
            return;
          }

          if (action === 'start-ui') {
            out.textContent = 'UI уже запущен. Открой/обнови страницу http://127.0.0.1:8787/';
            return;
          }

          const payload = { action };
          if (action === 'api-login') {
            const em = cmd.match(/-Email\s+"([^"]+)"/i);
            const pw = cmd.match(/-Password\s+"([^"]+)"/i);
            if (em) payload.email = em[1];
            if (pw) payload.password = pw[1];
          }

          const data = await postJSON('/api/run', payload);
          out.textContent = data.output || '(no output)';
          return;
        }

        if (cmd.startsWith('curl.exe')) {
          if (cmd.includes('/api/test-cors')) {
            const data = await postJSON('/api/http', {
              method: 'GET',
              url: 'http://127.0.0.1:8000/api/test-cors',
              body: '{}',
              token: ''
            });
            out.textContent = data.output || '(no output)';
            return;
          }

          if (cmd.includes('/api/login')) {
            const data = await postJSON('/api/http', {
              method: 'POST',
              url: 'http://127.0.0.1:8000/api/login',
              body: '{"email":"test@gmail.com","password":"11111111"}',
              token: ''
            });
            out.textContent = data.output || '(no output)';
            return;
          }

          if (cmd.includes('/api/profile')) {
            const tokenInput = document.getElementById('httpToken').value.trim();
            if (!tokenInput) {
              out.textContent = 'Для /api/profile нужен токен. Вставь его в поле Bearer token в HTTP Test Lab.';
              return;
            }
            const data = await postJSON('/api/http', {
              method: 'GET',
              url: 'http://127.0.0.1:8000/api/profile',
              body: '{}',
              token: tokenInput
            });
            out.textContent = data.output || '(no output)';
            return;
          }
        }

        out.textContent = 'Неподдерживаемый формат команды:\n' + cmd;
      } catch (err) {
        out.textContent = 'Run error: ' + (err?.message || err);
      }
    }

    async function postJSON(url, body) {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      return await res.json();
    }

    async function runAction(action) {
      const out = document.getElementById('out');
      out.textContent = 'Running: ' + action + ' ...';
      const data = await postJSON('/api/run', { action });
      out.textContent = data.output || '';
    }

    async function apiLogin() {
      const out = document.getElementById('out');
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      out.textContent = 'Running: api-login ...';
      const data = await postJSON('/api/run', { action: 'api-login', email, password });
      out.textContent = data.output || '';
    }

    function preset(name) {
      const method = document.getElementById('httpMethod');
      const url = document.getElementById('httpUrl');
      const body = document.getElementById('httpBody');
      const token = document.getElementById('httpToken');

      if (name === 'cors') {
        method.value = 'GET';
        url.value = 'http://127.0.0.1:8000/api/test-cors';
        body.value = '{}';
        token.value = '';
      }

      if (name === 'login') {
        method.value = 'POST';
        url.value = 'http://127.0.0.1:8000/api/login';
        body.value = '{"email":"test@gmail.com","password":"11111111"}';
      }

      if (name === 'profile') {
        method.value = 'GET';
        url.value = 'http://127.0.0.1:8000/api/profile';
        body.value = '{}';
      }
    }

    async function runHttp() {
      const out = document.getElementById('out');
      const method = document.getElementById('httpMethod').value.trim();
      const url = document.getElementById('httpUrl').value.trim();
      const body = document.getElementById('httpBody').value.trim();
      const token = document.getElementById('httpToken').value.trim();
      out.textContent = 'Running custom HTTP request ...';
      const data = await postJSON('/api/http', { method, url, body, token });
      out.textContent = data.output || '';
    }
    renderTaskTiles();
  </script>
</body>
</html>
"""


def run_shortcut(action: str, email: str | None = None, password: str | None = None) -> str:
    cmd = [
        "powershell",
        "-ExecutionPolicy",
        "Bypass",
        "-File",
        str(SHORTCUTS),
        action,
    ]
    if action == "api-login":
        if email:
            cmd.extend(["-Email", email])
        if password:
            cmd.extend(["-Password", password])

    result = subprocess.run(
        cmd,
        cwd=str(ROOT),
        text=True,
        capture_output=True,
        timeout=45,
        check=False,
    )

    output = (result.stdout or "").strip()
    err = (result.stderr or "").strip()
    if err:
        output = f"{output}\n{err}".strip()
    if not output:
        output = "(no output)"

    return output


def run_http_request(method: str, url: str, body: str, token: str | None = None) -> str:
    method_up = (method or "GET").upper()
    headers = {"Accept": "application/json"}
    data: bytes | None = None

    if token:
        headers["Authorization"] = f"Bearer {token}"

    if method_up in {"POST", "PUT", "PATCH", "DELETE"}:
        payload_text = body.strip() if body else "{}"
        try:
            payload_obj = json.loads(payload_text)
        except json.JSONDecodeError:
            payload_obj = {"raw": payload_text}
        payload = json.dumps(payload_obj).encode("utf-8")
        data = payload
        headers["Content-Type"] = "application/json"

    req = Request(url=url, data=data, method=method_up, headers=headers)

    try:
        with urlopen(req, timeout=30) as resp:
            status = resp.status
            resp_headers = dict(resp.headers.items())
            content = resp.read().decode("utf-8", errors="replace")
    except HTTPError as exc:
        status = exc.code
        resp_headers = dict(exc.headers.items()) if exc.headers else {}
        content = exc.read().decode("utf-8", errors="replace")
    except URLError as exc:
        return f"Request error: {exc}"

    out = []
    out.append(f"HTTP {status}")
    out.append("")
    out.append("Response headers:")
    out.append(json.dumps(resp_headers, ensure_ascii=False, indent=2))
    out.append("")
    out.append("Response body:")
    out.append(content if content else "(empty)")
    return "\n".join(out)


class Handler(BaseHTTPRequestHandler):
    def _send_json(self, payload: dict, code: int = 200) -> None:
        data = json.dumps(payload).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def do_GET(self) -> None:
        parsed = urlparse(self.path)
        if parsed.path != "/":
            self.send_error(404, "Not found")
            return

        data = HTML.encode("utf-8")
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def do_POST(self) -> None:
        parsed = urlparse(self.path)
        if parsed.path not in {"/api/run", "/api/http"}:
            self.send_error(404, "Not found")
            return

        length = int(self.headers.get("Content-Length", "0"))
        raw = self.rfile.read(length) if length > 0 else b"{}"
        try:
            body = json.loads(raw.decode("utf-8"))
        except json.JSONDecodeError:
            self._send_json({"output": "Invalid JSON"}, 400)
            return

        if parsed.path == "/api/run":
            action = str(body.get("action", "")).strip()
            if not action:
                self._send_json({"output": "Missing action"}, 400)
                return

            allowed = {"help", "open-project", "open-browser", "open-login", "api-login", "db-ping"}
            if action not in allowed:
                self._send_json({"output": f"Action is not allowed: {action}"}, 400)
                return

            email = body.get("email")
            password = body.get("password")

            try:
                if action == "api-login":
                    payload = json.dumps(
                        {
                            "email": email or "test@example.com",
                            "password": password or "password",
                        }
                    )
                    output = run_http_request(
                        method="POST",
                        url="http://127.0.0.1:8000/api/login",
                        body=payload,
                        token=None,
                    )
                    self._send_json({"output": output})
                    return

                output = run_shortcut(action, email=email, password=password)
                self._send_json({"output": output})
            except subprocess.TimeoutExpired:
                self._send_json({"output": f"Command timeout: {action}"}, 504)
            except Exception as exc:  # noqa: BLE001
                self._send_json({"output": f"Error: {exc}"}, 500)
            return

        method = str(body.get("method", "GET"))
        target_url = str(body.get("url", "")).strip()
        payload = str(body.get("body", "{}"))
        token = str(body.get("token", "")).strip()
        if not target_url:
            self._send_json({"output": "Missing url"}, 400)
            return

        try:
            output = run_http_request(method=method, url=target_url, body=payload, token=token)
            self._send_json({"output": output})
        except Exception as exc:  # noqa: BLE001
            self._send_json({"output": f"Error: {exc}"}, 500)

    def log_message(self, format: str, *args: object) -> None:  # noqa: A003
        return


def main() -> int:
    if not SHORTCUTS.exists():
        print(f"shortcuts script not found: {SHORTCUTS}")
        return 1

    server = ThreadingHTTPServer((HOST, PORT), Handler)
    print(f"Dev UI: http://{HOST}:{PORT}")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()

    return 0


if __name__ == "__main__":
    sys.exit(main())
