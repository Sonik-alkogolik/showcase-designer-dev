# Переработка sidebar и чистка верхнего navbar

Обновлено: 2026-04-19  
Файл правок: `Edits_19.04.2026_м_02.md`

## Что изменено

1. Sidebar (dashboard):
- меню переведено с плоского списка на группировку:
  - `Операции`
  - `Каталог`
  - `Система`
- добавлены быстрые действия сверху (`Новый товар`, `Новый заказ`);
- улучшен адаптив: на мобильных группы не ломают сетку;
- sidebar получил прокрутку по высоте (`overflow-y: auto`).

2. Navbar:
- удален блок:
  - `<div class="user-info"><span>Пользователь</span></div>`
- удалены связанные неиспользуемые данные/стили.

3. Layout:
- `DashboardLayout` переключен на новую структуру sidebar:
  - `groups`
  - `quickActions`

## Файлы

- `client/src/config/dashboardMenu.js`
- `client/src/components/dashboard/DashboardSidebar.vue`
- `client/src/views/dashboard/DashboardLayout.vue`
- `client/src/components/Navbar.vue`

