from __future__ import annotations

from dataclasses import dataclass


@dataclass
class AutoTestConfig:
    base_url: str
    email: str
    password: str
    shop_id: str
    timeout: float
    bot_token: str
    chat_id: str
    telegram_username: str
    allow_mutation: bool
