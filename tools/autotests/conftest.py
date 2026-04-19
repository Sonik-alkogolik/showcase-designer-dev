from __future__ import annotations

import os
from pathlib import Path
from typing import Iterator

import pytest
import requests

from .config import AutoTestConfig

def pytest_addoption(parser: pytest.Parser) -> None:
    parser.addoption("--base-url", action="store", default=os.getenv("AUTO_BASE_URL", "https://e-tgo.ru"))
    parser.addoption("--email", action="store", default=os.getenv("AUTO_TEST_EMAIL", ""))
    parser.addoption("--password", action="store", default=os.getenv("AUTO_TEST_PASSWORD", ""))
    parser.addoption("--shop-id", action="store", default=os.getenv("AUTO_TEST_SHOP_ID", ""))
    parser.addoption("--timeout", action="store", default=os.getenv("AUTO_TEST_TIMEOUT", "20"))


def pytest_configure(config: pytest.Config) -> None:
    config.addinivalue_line("markers", "prod_smoke: safe smoke checks for production")


@pytest.fixture(scope="session")
def test_config(pytestconfig: pytest.Config) -> AutoTestConfig:
    base_url = str(pytestconfig.getoption("--base-url")).rstrip("/")
    return AutoTestConfig(
        base_url=base_url,
        email=str(pytestconfig.getoption("--email")).strip(),
        password=str(pytestconfig.getoption("--password")).strip(),
        shop_id=str(pytestconfig.getoption("--shop-id")).strip(),
        timeout=float(pytestconfig.getoption("--timeout")),
    )


@pytest.fixture(scope="session")
def http_session(test_config: AutoTestConfig) -> Iterator[requests.Session]:
    session = requests.Session()
    session.headers.update({"Accept": "application/json"})
    yield session
    session.close()


@pytest.fixture(scope="session")
def report_dir() -> Path:
    path = Path("tools/autotest-reports")
    path.mkdir(parents=True, exist_ok=True)
    return path
