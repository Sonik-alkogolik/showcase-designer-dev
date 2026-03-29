<?php

return [
    'telegram_webapp' => [
        // Enable strict Telegram WebApp initData verification on protected public endpoints.
        'enforce' => (bool) env('TELEGRAM_INITDATA_ENFORCE', false),
        // How long initData is considered valid (in seconds) based on auth_date.
        'max_age_seconds' => (int) env('TELEGRAM_INITDATA_MAX_AGE_SECONDS', 3600),
    ],
];
