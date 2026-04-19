from __future__ import annotations

import json
import time
from pathlib import Path

import pytest
import requests

from .config import AutoTestConfig


def _save_json(report_dir: Path, name: str, payload: dict) -> None:
    (report_dir / name).write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")


def _require_mutation_enabled(test_config: AutoTestConfig) -> None:
    if not test_config.allow_mutation:
        pytest.skip("Mutation tests are disabled. Set AUTO_TEST_ALLOW_MUTATION=1 or pass --allow-mutation")


def _login(http_session: requests.Session, test_config: AutoTestConfig) -> str:
    response = http_session.post(
        f"{test_config.base_url}/api/login",
        json={"email": test_config.email, "password": test_config.password},
        timeout=test_config.timeout,
    )
    assert response.status_code == 200, response.text
    token = response.json().get("token")
    assert token, "No auth token in /api/login response"
    return str(token)


@pytest.mark.prod_mutation
def test_mutation_product_create_delete_and_bot_toggle(
    http_session: requests.Session,
    test_config: AutoTestConfig,
    report_dir: Path,
) -> None:
    _require_mutation_enabled(test_config)
    if not test_config.email or not test_config.password or not test_config.shop_id:
        pytest.skip("AUTO_TEST_EMAIL/AUTO_TEST_PASSWORD/AUTO_TEST_SHOP_ID are required")
    if not test_config.bot_token or not test_config.chat_id:
        pytest.skip("AUTO_TEST_BOT_TOKEN and AUTO_TEST_CHAT_ID are required for bot checks")

    token = _login(http_session, test_config)
    auth_headers = {"Authorization": f"Bearer {token}", "Accept": "application/json"}
    shop_id = test_config.shop_id
    stamp = int(time.time())

    # Read current shop state to restore after mutation.
    shop_info_response = http_session.get(
        f"{test_config.base_url}/api/shops/{shop_id}",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert shop_info_response.status_code == 200, shop_info_response.text
    shop_info = shop_info_response.json().get("shop", {})

    bot_token_response = http_session.get(
        f"{test_config.base_url}/api/shops/{shop_id}/bot-token",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert bot_token_response.status_code == 200, bot_token_response.text
    original_bot_token = bot_token_response.json().get("bot_token")

    restore_payload = {
        "name": shop_info.get("name"),
        "delivery_name": shop_info.get("delivery_name"),
        "delivery_price": shop_info.get("delivery_price"),
        "notification_chat_id": shop_info.get("notification_chat_id"),
        "notification_username": shop_info.get("notification_username"),
        "webhook_url": shop_info.get("webhook_url"),
        "bot_token": original_bot_token,
    }

    update_payload = {
        "notification_chat_id": test_config.chat_id,
        "notification_username": f"@{test_config.telegram_username.lstrip('@')}",
        "bot_token": test_config.bot_token,
    }

    update_response = http_session.put(
        f"{test_config.base_url}/api/shops/{shop_id}",
        headers=auth_headers,
        json=update_payload,
        timeout=test_config.timeout,
    )
    assert update_response.status_code in (200, 201), update_response.text

    connect_response = http_session.post(
        f"{test_config.base_url}/api/shops/{shop_id}/bot-connect",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert connect_response.status_code in (200, 422), connect_response.text

    bot_status_response = http_session.get(
        f"{test_config.base_url}/api/shops/{shop_id}/bot-status",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert bot_status_response.status_code == 200, bot_status_response.text

    product_name = f"AUTOTEST_PRODUCT_{stamp}"
    create_product_response = http_session.post(
        f"{test_config.base_url}/api/shops/{shop_id}/products",
        headers=auth_headers,
        json={
            "name": product_name,
            "price": 123,
            "description": "autotest prod mutation",
            "category": "AUTOTEST",
            "in_stock": True,
        },
        timeout=test_config.timeout,
    )
    assert create_product_response.status_code in (200, 201), create_product_response.text
    product = create_product_response.json().get("product", {})
    product_id = product.get("id")
    assert product_id, "No product id in create response"

    delete_response = http_session.delete(
        f"{test_config.base_url}/api/shops/{shop_id}/products/{product_id}",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert delete_response.status_code == 200, delete_response.text

    # restore original shop settings
    restore_response = http_session.put(
        f"{test_config.base_url}/api/shops/{shop_id}",
        headers=auth_headers,
        json=restore_payload,
        timeout=test_config.timeout,
    )
    assert restore_response.status_code in (200, 201), restore_response.text

    _save_json(
        report_dir,
        "mutation_shop_bot_product.json",
        {
            "shop_update_status": update_response.status_code,
            "bot_connect_status": connect_response.status_code,
            "bot_status_status": bot_status_response.status_code,
            "product_create_status": create_product_response.status_code,
            "product_delete_status": delete_response.status_code,
            "shop_restore_status": restore_response.status_code,
            "created_product_id": product_id,
            "created_product_name": product_name,
        },
    )


@pytest.mark.prod_mutation
def test_mutation_unlink_and_relink_telegram(
    http_session: requests.Session,
    test_config: AutoTestConfig,
    report_dir: Path,
) -> None:
    _require_mutation_enabled(test_config)
    if not test_config.email or not test_config.password or not test_config.chat_id:
        pytest.skip("AUTO_TEST_EMAIL/AUTO_TEST_PASSWORD/AUTO_TEST_CHAT_ID are required")

    token = _login(http_session, test_config)
    auth_headers = {"Authorization": f"Bearer {token}", "Accept": "application/json"}

    profile_before = http_session.get(
        f"{test_config.base_url}/api/profile",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert profile_before.status_code == 200, profile_before.text

    unlink_response = http_session.delete(
        f"{test_config.base_url}/api/profile/telegram/unlink",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert unlink_response.status_code in (200, 400), unlink_response.text

    generate_response = http_session.post(
        f"{test_config.base_url}/api/profile/telegram/generate-token",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert generate_response.status_code == 200, generate_response.text
    link_token = generate_response.json().get("token")
    assert link_token, "No telegram link token generated"

    webhook_payload = {
        "update_id": int(time.time()),
        "message": {
            "message_id": 1,
            "text": f"/start {link_token}",
            "chat": {"id": int(test_config.chat_id), "type": "private"},
            "from": {
                "id": int(test_config.chat_id),
                "is_bot": False,
                "first_name": "Auto",
                "username": test_config.telegram_username.lstrip("@"),
            },
        },
    }
    webhook_response = http_session.post(
        f"{test_config.base_url}/api/telegram/webhook",
        json=webhook_payload,
        timeout=test_config.timeout,
    )
    assert webhook_response.status_code == 200, webhook_response.text

    profile_after = http_session.get(
        f"{test_config.base_url}/api/profile",
        headers=auth_headers,
        timeout=test_config.timeout,
    )
    assert profile_after.status_code == 200, profile_after.text
    profile_data = profile_after.json()
    assert bool(profile_data.get("telegram_linked")) is True
    assert str(profile_data.get("telegram_id")) == str(test_config.chat_id)

    _save_json(
        report_dir,
        "mutation_telegram_relink.json",
        {
            "unlink_status": unlink_response.status_code,
            "generate_token_status": generate_response.status_code,
            "webhook_status": webhook_response.status_code,
            "profile_after_status": profile_after.status_code,
            "telegram_linked_after": profile_data.get("telegram_linked"),
            "telegram_id_after": profile_data.get("telegram_id"),
        },
    )

