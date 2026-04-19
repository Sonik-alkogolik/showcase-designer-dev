from __future__ import annotations

import argparse
import os
from pathlib import Path

import pytest
from dotenv import load_dotenv


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Run production-safe smoke autotests")
    parser.add_argument(
        "--env-file",
        default="tools/autotests/.env.prod",
        help="Path to env file with AUTO_* variables",
    )
    parser.add_argument(
        "--marker",
        default="prod_smoke",
        help="Pytest marker expression to run",
    )
    parser.add_argument(
        "--include-mutation",
        action="store_true",
        help="Run mutation tests too (requires AUTO_TEST_ALLOW_MUTATION=1)",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    env_path = Path(args.env_file)
    if env_path.exists():
        load_dotenv(env_path)

    os.makedirs("tools/autotest-reports", exist_ok=True)
    marker_expr = args.marker
    if args.include_mutation and marker_expr.strip() == "prod_smoke":
        marker_expr = "prod_smoke or prod_mutation"

    return pytest.main(
        [
            "-m",
            marker_expr,
            "-q",
            "--disable-warnings",
            "--maxfail=1",
            "--junitxml=tools/autotest-reports/prod-smoke-junit.xml",
            "tools/autotests",
        ]
    )


if __name__ == "__main__":
    raise SystemExit(main())
