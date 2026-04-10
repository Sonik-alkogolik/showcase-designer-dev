# Sidebar Routes: План Реализации (Vue)

Обновлено: 2026-04-10  
Цель: реализовать левое боковое меню как в макете, где каждый пункт ведет на отдельный route и отдельную страницу.

## 1) Пункты меню и target-роуты

1. `Товары` -> `/dashboard/products`
2. `Заказы` -> `/dashboard/orders`
3. `Маркетинг` -> `/dashboard/marketing`
4. `Аналитика` -> `/dashboard/analytics`
5. `Настройки` -> `/dashboard/settings`
6. `Помощь` -> `/dashboard/help`
7. `Язык` -> `/dashboard/language`
8. `Профиль` -> `/dashboard/profile`

## 2) Архитектура (новый раздел Dashboard)

1. Создать layout-контейнер `DashboardLayout.vue`:
   - левая колонка: sidebar меню;
   - правая колонка: `router-view` для страниц раздела.
2. Создать единый sidebar-компонент `DashboardSidebar.vue`:
   - иконка + название пункта;
   - активный пункт по текущему route;
   - режим `collapsed` (иконки без текста).
3. Вынести конфиг меню в отдельный файл:
   - `client/src/config/dashboardMenu.js`
   - один источник правды для route + label + icon.

## 3) Новые Vue-страницы (по одной на пункт)

Создать страницы-заглушки с правильной структурой и заголовками:

1. `client/src/views/dashboard/DashboardProductsView.vue`
2. `client/src/views/dashboard/DashboardOrdersView.vue`
3. `client/src/views/dashboard/DashboardMarketingView.vue`
4. `client/src/views/dashboard/DashboardAnalyticsView.vue`
5. `client/src/views/dashboard/DashboardSettingsView.vue`
6. `client/src/views/dashboard/DashboardHelpView.vue`
7. `client/src/views/dashboard/DashboardLanguageView.vue`
8. `client/src/views/dashboard/DashboardProfileView.vue`

Для каждой страницы:
1. Title/Subtitle по пункту меню.
2. Контейнеры под будущие данные (каркас секций).
3. Loading/empty/error блоки в едином стиле.

## 4) Роутинг

Обновить `client/src/router/index.js`:

1. Добавить родительский route `/dashboard` с `DashboardLayout`.
2. Добавить дочерние routes для каждого пункта:
   - `products`, `orders`, `marketing`, `analytics`, `settings`, `help`, `language`, `profile`.
3. Для всех dashboard-роутов включить `meta.requiresAuth = true`.
4. Добавить redirect:
   - `/dashboard` -> `/dashboard/products`.
5. Сохранить обратную совместимость:
   - текущие старые роуты пока не удалять;
   - после миграции сделать отдельный cleanup.

## 5) Интеграция с текущим UI

1. В `Navbar.vue` оставить верхнюю навигацию минимальной.
2. Добавить вход в новый раздел:
   - кнопка/ссылка `Панель` -> `/dashboard`.
3. Убедиться, что WebApp (`/app`) не затрагивается и работает как раньше.

## 6) Поэтапная реализация

### Этап A: Skeleton
1. `DashboardLayout` + `DashboardSidebar`.
2. Все 8 route + 8 страниц-заглушек.
3. Базовая адаптивность (desktop + mobile).

### Этап B: Привязка данных
1. `Товары`: связать с текущими товарами магазина.
2. `Заказы`: список заказов + статусы.
3. `Настройки`: интегрировать существующую логику настроек магазина.
4. `Профиль`: использовать существующие данные профиля.

### Этап C: Остальные разделы
1. `Маркетинг`, `Аналитика`, `Помощь`, `Язык` — сначала MVP-блоки.
2. Добавить реальные API/данные в следующих итерациях.

## 7) Definition of Done

1. Все пункты бокового меню кликабельны.
2. Каждый пункт открывает отдельный route и отдельный Vue-view.
3. Активный пункт корректно подсвечивается.
4. Перезагрузка страницы на любом `/dashboard/*` сохраняет корректный экран.
5. На mobile sidebar не ломает контент.
6. Никаких регрессий в текущем `/app` и существующих auth-переходах.

## 8) Технические заметки

1. Иконки взять из единого набора (`lucide-vue-next` или локальный SVG-спрайт).
2. Не дублировать route-имена; придерживаться префикса `Dashboard*`.
3. Все новые компоненты делать в `client/src/views/dashboard` и `client/src/components/dashboard`.
4. Перед merge: smoke-прогон переходов по всем новым routes вручную.

