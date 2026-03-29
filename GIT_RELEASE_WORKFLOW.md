# Git Release Workflow

Обновлено: 2026-03-29

## Назначение репозиториев

- `origin` -> `showcase-designer-dev` (репозиторий разработки)
- `prod` -> `showcase-designer` (production-репозиторий, сервер `e-tgo.ru`)

## Ключевое решение

- Работаем без feature-веток.
- Используем только ветку `main`.
- Два репозитория заменяют разделение по веткам:
  - `origin/main` = разработка и промежуточные обновления
  - `prod/main` = только релизные изменения для пользователей

## Базовый цикл работы

1. Пишем код локально в `main`.
2. Коммитим локально в `main`.
3. Отправляем в dev-репозиторий:
   - `git push origin main`
4. После проверки и готовности к релизу отправляем тот же `main` в production-репозиторий:
   - `git push prod main`

## Обновление production сервера

После `git push prod main` на сервере в директории проекта выполнить:

1. `git pull`
2. `composer install --no-dev --optimize-autoloader` (если изменялись зависимости)
3. `php artisan migrate --force` (если есть миграции)
4. `php artisan optimize:clear`
5. `php artisan optimize`

## Минимальные проверки перед `git push prod main`

1. Рабочий сценарий проходит локально (минимум: логин -> магазин -> товары -> webapp).
2. Нет незакоммиченных изменений:
   - `git status --short --branch`
3. Понимаем, какие коммиты уйдут в прод:
   - `git log --oneline prod/main..main`

## Контроль синхронизации

- Проверка расхождений с dev:
  - `git rev-list --left-right --count origin/main...main`
- Проверка расхождений с prod:
  - `git rev-list --left-right --count prod/main...main`

Когда локальный `main` уже отправлен и в `origin`, и в `prod`, ожидаем:

- `origin/main...main` -> `0 0`
- `prod/main...main` -> `0 0`
