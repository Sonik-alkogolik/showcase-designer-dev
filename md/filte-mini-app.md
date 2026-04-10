# Filte Mini App Plan

## Goal
Доработать Telegram WebApp витрину под mini-app UX:
- сверху категории + фильтрация по ним;
- нижняя навигация: `Главная / Избранное / Корзина / Профиль`;
- слайдер перед карточками товаров;
- управление попаданием товара в слайдер из web-админки товара.

## Scope
1. Backend:
   - добавить флаг товара `show_in_slider` (bool, default `false`);
   - поддержать поле в `store/update` API для товаров;
   - отдавать флаг в публичном каталоге WebApp.
2. Web-админка:
   - чекбокс в карточке товара "Добавить в слайдер";
   - индикатор в списке товаров.
3. Telegram WebApp UI:
   - категории в верхней зоне (чипы + быстрый фильтр);
   - слайдер баннеров над товарной сеткой;
   - нижний tab bar на 4 кнопки;
   - экраны `Избранное` и `Профиль` в текущем стиле;
   - избранное в `localStorage` с изоляцией по магазину.

## Step-by-step
1. Data layer (backend + migration)
   - [x] migration `products.show_in_slider`;
   - [x] `Product` model (`fillable`, `casts`);
   - [x] `ProductController` (`store/update` validation + save).
2. Admin products UI
   - [x] чекбокс `show_in_slider` в форме создания/редактирования;
   - [x] метка "В слайдере" в карточке товара.
3. WebApp catalog UX
   - [x] верхние category chips + фильтрация;
   - [x] hero-slider блок перед сеткой карточек;
   - [x] кнопка избранного на карточке.
4. Bottom navigation
   - [x] tab bar (`home`, `favorites`, `cart`, `profile`);
   - [x] экраны favorites/profile в рамках `WebAppView`.
5. QA
   - [x] smoke: создание товара с `show_in_slider=true` и видимость в слайдере (feature tests + build);
   - [ ] smoke: переключение табов, добавление/удаление избранного, корзина;
   - [ ] smoke: фильтрация категориями.

## Current status
- [x] План зафиксирован.
- [x] Step 1 complete.
- [x] Step 2 complete.
- [x] Step 3 complete.
- [x] Step 4 complete.
- [ ] Step 5 manual smoke in progress.
