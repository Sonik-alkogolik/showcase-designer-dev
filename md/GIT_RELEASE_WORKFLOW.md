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
3. Обязательно отправляем в dev-репозиторий:
   - `git push origin main`
4. Обязательно отправляем тот же `main` в production-репозиторий:
   - `git push prod main`

Это правило не пропускаем: каждый релизный коммит должен быть и в `origin/main`, и в `prod/main`.

## Обновление production сервера

После `git push prod main` выполните на сервере полный цикл ниже (строго по шагам):

```bash
# 0) перейти в проект и проверить текущую ветку/состояние
cd /var/www/showcase-designer
pwd
git status --short --branch

# 1) подтянуть изменения (fast-forward only)
cd /var/www/showcase-designer
git pull --ff-only origin main

# 2) установить/обновить backend зависимости (если менялся composer.lock)
cd /var/www/showcase-designer
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

# 3) применить миграции (обязательно, если в релизе есть database/migrations)
cd /var/www/showcase-designer
php artisan migrate --force

# 4) собрать frontend
cd /var/www/showcase-designer/client
npm ci
npm run build

# 5) опубликовать собранный frontend в public
cd /var/www/showcase-designer
rm -f public/index.html
rm -rf public/assets
cp client/dist/index.html public/
cp -r client/dist/assets public/

# 6) очистить кэши Laravel и прогреть рабочие
cd /var/www/showcase-designer
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7) быстрые проверки после релиза
cd /var/www/showcase-designer
php artisan about
php artisan queue:restart
```

Если `npm ci` недоступен из-за lock-файла/окружения, используйте:
- `cd /var/www/showcase-designer/client && npm install && npm run build`

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
