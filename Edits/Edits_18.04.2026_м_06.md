# Этап 5: категории товара (multi-select + many-to-many)

Обновлено: 2026-04-18
Файл правок: `Edits_18.04.2026_м_06.md`

## Что исправлено

1. В форме товара поле категории переведено с text-input на выпадающий список с множественным выбором.
2. Добавлена возможность назначать один товар сразу в несколько категорий.
3. Исправлено сохранение при снятии категории: теперь можно очистить категории и корректно сохранить изменения.
4. В карточке товара выводятся все категории, а не только одна.

## Архитектурные изменения

1. Добавлена pivot-таблица `category_product` (many-to-many `products <-> categories`).
2. Сделан backfill: текущая `products.category_id` переносится в pivot при миграции.
3. API товаров теперь работает с `category_ids`:
- create/update принимают массив категорий;
- list/public list отдают связи `categories`;
- фильтрация по категории учитывает и primary, и pivot-связи.
4. Удалена автоподстановка категории в `Product` model, которая мешала явно очищать категорию при редактировании.

## Измененные файлы

1. `database/migrations/2026_04_18_183000_create_category_product_table.php`
2. `app/Models/Product.php`
3. `app/Models/Category.php`
4. `app/Http/Controllers/ProductController.php`
5. `app/Http/Controllers/Api/CategoryController.php`
6. `client/src/views/shop/ProductsView.vue`

## Проверка

1. `npm run build` выполнен успешно.
