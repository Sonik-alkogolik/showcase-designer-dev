from __future__ import annotations

import json
from pathlib import Path

import pytest
import requests

from .config import AutoTestConfig


def _save_json(report_dir: Path, name: str, payload: dict) -> None:
    (report_dir / name).write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")


@pytest.mark.prod_smoke
def test_public_root_available(http_session: requests.Session, test_config: AutoTestConfig, report_dir: Path) -> None:
    url = f"{test_config.base_url}/"
    response = http_session.get(url, timeout=test_config.timeout)

    _save_json(
        report_dir,
        "public_root.json",
        {"url": url, "status_code": response.status_code, "ok": response.ok},
    )

    assert response.status_code == 200


@pytest.mark.prod_smoke
def test_login_profile_and_logout(http_session: requests.Session, test_config: AutoTestConfig, report_dir: Path) -> None:
    if not test_config.email or not test_config.password:
        pytest.skip("AUTO_TEST_EMAIL/AUTO_TEST_PASSWORD are not set")

    login_url = f"{test_config.base_url}/api/login"
    login_response = http_session.post(
        login_url,
        json={"email": test_config.email, "password": test_config.password},
        timeout=test_config.timeout,
    )
    assert login_response.status_code == 200, login_response.text

    token = login_response.json().get("token")
    assert token, "No auth token in /api/login response"

    auth_headers = {"Authorization": f"Bearer {token}", "Accept": "application/json"}

    profile_url = f"{test_config.base_url}/api/profile"
    profile_response = http_session.get(profile_url, headers=auth_headers, timeout=test_config.timeout)

    shops_url = f"{test_config.base_url}/api/shops"
    shops_response = http_session.get(shops_url, headers=auth_headers, timeout=test_config.timeout)

    plans_url = f"{test_config.base_url}/api/subscription/plans"
    plans_response = http_session.get(plans_url, headers=auth_headers, timeout=test_config.timeout)

    logout_url = f"{test_config.base_url}/api/logout"
    logout_response = http_session.post(logout_url, headers=auth_headers, timeout=test_config.timeout)

    _save_json(
        report_dir,
        "auth_profile_smoke.json",
        {
            "login": {"url": login_url, "status_code": login_response.status_code},
            "profile": {"url": profile_url, "status_code": profile_response.status_code},
            "shops": {"url": shops_url, "status_code": shops_response.status_code},
            "plans": {"url": plans_url, "status_code": plans_response.status_code},
            "logout": {"url": logout_url, "status_code": logout_response.status_code},
        },
    )

    assert profile_response.status_code == 200, profile_response.text
    assert shops_response.status_code == 200, shops_response.text
    assert plans_response.status_code == 200, plans_response.text
    assert logout_response.status_code in (200, 204), logout_response.text


@pytest.mark.prod_smoke
def test_public_shop_endpoint(http_session: requests.Session, test_config: AutoTestConfig, report_dir: Path) -> None:
    if not test_config.shop_id:
        pytest.skip("AUTO_TEST_SHOP_ID is not set")

    url = f"{test_config.base_url}/api/shops/{test_config.shop_id}/public"
    response = http_session.get(url, timeout=test_config.timeout)

    _save_json(
        report_dir,
        "public_shop.json",
        {
            "url": url,
            "status_code": response.status_code,
            "ok": response.ok,
        },
    )

    assert response.status_code == 200, response.text
