<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedDemoShopsCommand extends Command
{
    protected $signature = 'demo:seed-shops {--force : Пересоздать товары в демо-магазинах}';

    protected $description = 'Создать 2 демо-аккаунта и наполнить магазины товарами (20+ каждый).';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $userA = User::updateOrCreate(
            ['email' => 'demo.owner.a@e-tgo.local'],
            ['name' => 'Demo Owner A', 'password' => Hash::make('DemoPass123!')]
        );
        $userB = User::updateOrCreate(
            ['email' => 'demo.owner.b@e-tgo.local'],
            ['name' => 'Demo Owner B', 'password' => Hash::make('DemoPass123!')]
        );

        $shopA = Shop::updateOrCreate(
            ['user_id' => $userA->id, 'name' => 'Demo Services A'],
            [
                'delivery_name' => 'Курьер',
                'delivery_price' => 350,
                'theme_settings' => [
                    'background_start' => '#091126',
                    'background_end' => '#14284A',
                    'text_color' => '#F2F7FF',
                    'dots_color' => '#5FE1FF',
                ],
            ]
        );

        $shopB = Shop::updateOrCreate(
            ['user_id' => $userB->id, 'name' => 'Demo Services B'],
            [
                'delivery_name' => 'Самовывоз / доставка',
                'delivery_price' => 290,
                'theme_settings' => [
                    'background_start' => '#0F1A12',
                    'background_end' => '#1F3522',
                    'text_color' => '#EFFCEF',
                    'dots_color' => '#6AF27D',
                ],
            ]
        );

        if ($force) {
            Product::query()->where('shop_id', $shopA->id)->delete();
            Product::query()->where('shop_id', $shopB->id)->delete();
        }

        $this->seedShopData($shopA, $this->shopAData());
        $this->seedShopData($shopB, $this->shopBData());

        $this->info('Готово: созданы/обновлены 2 демо-аккаунта и заполнены товары.');
        $this->line('Логины: demo.owner.a@e-tgo.local и demo.owner.b@e-tgo.local');
        $this->line('Пароль: DemoPass123!');

        return self::SUCCESS;
    }

    private function seedShopData(Shop $shop, array $data): void
    {
        foreach ($data as $group) {
            $category = Category::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => $group['category']],
                ['slug' => Str::slug($group['category']), 'is_active' => true, 'sort_order' => 0]
            );

            foreach ($group['items'] as $index => $item) {
                $product = Product::updateOrCreate(
                    ['shop_id' => $shop->id, 'name' => $item['name']],
                    [
                        'category_id' => $category->id,
                        'category' => $group['category'],
                        'price' => $item['price'],
                        'description' => $item['description'],
                        'image' => $item['image'],
                        'in_stock' => true,
                        'show_in_slider' => $index < 3,
                    ]
                );

                $product->categories()->syncWithoutDetaching([$category->id]);
            }
        }
    }

    private function shopAData(): array
    {
        return [
            [
                'category' => 'Мастера маникюра',
                'items' => [
                    ['name' => 'Маникюр классический', 'price' => 1800, 'description' => 'Гигиеническая обработка, выравнивание и покрытие базой.', 'image' => 'https://images.pexels.com/photos/3993449/pexels-photo-3993449.jpeg'],
                    ['name' => 'Маникюр + гель-лак', 'price' => 2400, 'description' => 'Комбинированный маникюр с покрытием и укреплением.', 'image' => 'https://images.pexels.com/photos/704815/pexels-photo-704815.jpeg'],
                    ['name' => 'Снятие + новое покрытие', 'price' => 2600, 'description' => 'Снятие старого материала и нанесение нового оттенка.', 'image' => 'https://images.pexels.com/photos/7755652/pexels-photo-7755652.jpeg'],
                    ['name' => 'Дизайн 2 ногтя', 'price' => 500, 'description' => 'Акцентный минималистичный дизайн по вашему референсу.', 'image' => 'https://images.pexels.com/photos/939836/pexels-photo-939836.jpeg'],
                    ['name' => 'SPA-уход для рук', 'price' => 900, 'description' => 'Пилинг, маска и увлажнение для мягкости кожи.', 'image' => 'https://images.pexels.com/photos/5069608/pexels-photo-5069608.jpeg'],
                ],
            ],
            [
                'category' => 'Барберы',
                'items' => [
                    ['name' => 'Мужская стрижка', 'price' => 1700, 'description' => 'Форма под тип лица, укладка и консультация.', 'image' => 'https://images.pexels.com/photos/1813272/pexels-photo-1813272.jpeg'],
                    ['name' => 'Стрижка + борода', 'price' => 2400, 'description' => 'Комплекс: стрижка, контур бороды и укладка.', 'image' => 'https://images.pexels.com/photos/769739/pexels-photo-769739.jpeg'],
                    ['name' => 'Оформление бороды', 'price' => 1200, 'description' => 'Моделирование формы с бритьем опасной бритвой.', 'image' => 'https://images.pexels.com/photos/1319461/pexels-photo-1319461.jpeg'],
                    ['name' => 'Камуфляж седины', 'price' => 2100, 'description' => 'Тонирование седины без резкого контраста.', 'image' => 'https://images.pexels.com/photos/1453005/pexels-photo-1453005.jpeg'],
                    ['name' => 'Детская стрижка', 'price' => 1300, 'description' => 'Аккуратная стрижка для мальчиков до 12 лет.', 'image' => 'https://images.pexels.com/photos/1570807/pexels-photo-1570807.jpeg'],
                ],
            ],
            [
                'category' => 'Ремонт квартир',
                'items' => [
                    ['name' => 'Косметический ремонт 1м²', 'price' => 4500, 'description' => 'Шпаклевка, покраска, базовая отделка стен.', 'image' => 'https://images.pexels.com/photos/7937316/pexels-photo-7937316.jpeg'],
                    ['name' => 'Укладка ламината 1м²', 'price' => 650, 'description' => 'Подложка, подрезка и монтаж под уровень.', 'image' => 'https://images.pexels.com/photos/209296/pexels-photo-209296.jpeg'],
                    ['name' => 'Поклейка обоев 1м²', 'price' => 520, 'description' => 'Подготовка поверхности и аккуратная поклейка.', 'image' => 'https://images.pexels.com/photos/6474472/pexels-photo-6474472.jpeg'],
                    ['name' => 'Электрика (точка)', 'price' => 1800, 'description' => 'Монтаж одной розетки/выключателя с подключением.', 'image' => 'https://images.pexels.com/photos/257736/pexels-photo-257736.jpeg'],
                    ['name' => 'Сантехника (точка)', 'price' => 2200, 'description' => 'Установка и подключение точки водоснабжения.', 'image' => 'https://images.pexels.com/photos/5691629/pexels-photo-5691629.jpeg'],
                ],
            ],
            [
                'category' => 'Установка окон',
                'items' => [
                    ['name' => 'Замер окна', 'price' => 900, 'description' => 'Выезд мастера с точным замером проема.', 'image' => 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg'],
                    ['name' => 'Монтаж ПВХ окна', 'price' => 6800, 'description' => 'Демонтаж старого блока и установка нового.', 'image' => 'https://images.pexels.com/photos/2219024/pexels-photo-2219024.jpeg'],
                    ['name' => 'Установка откосов', 'price' => 2500, 'description' => 'Оформление внутренних откосов под ключ.', 'image' => 'https://images.pexels.com/photos/834892/pexels-photo-834892.jpeg'],
                    ['name' => 'Регулировка фурнитуры', 'price' => 1200, 'description' => 'Настройка прижима и плавности открывания створки.', 'image' => 'https://images.pexels.com/photos/302891/pexels-photo-302891.jpeg'],
                    ['name' => 'Москитная сетка', 'price' => 1600, 'description' => 'Изготовление и установка сетки по размеру.', 'image' => 'https://images.pexels.com/photos/276551/pexels-photo-276551.jpeg'],
                ],
            ],
            [
                'category' => 'Мелкие магазины',
                'items' => [
                    ['name' => 'Термокружка 450 мл', 'price' => 1590, 'description' => 'Металлическая кружка с герметичной крышкой.', 'image' => 'https://images.pexels.com/photos/302899/pexels-photo-302899.jpeg'],
                    ['name' => 'Ежедневник А5', 'price' => 790, 'description' => 'Плотная бумага, датированные страницы.', 'image' => 'https://images.pexels.com/photos/590493/pexels-photo-590493.jpeg'],
                    ['name' => 'Беспроводная колонка', 'price' => 3290, 'description' => 'Компактная колонка с Bluetooth и защитой от брызг.', 'image' => 'https://images.pexels.com/photos/63703/pexels-photo-63703.jpeg'],
                    ['name' => 'Настольная лампа LED', 'price' => 2490, 'description' => 'Регулировка яркости и цветовой температуры.', 'image' => 'https://images.pexels.com/photos/112811/pexels-photo-112811.jpeg'],
                    ['name' => 'Рюкзак городской', 'price' => 2890, 'description' => 'Усиленные лямки и отделение для ноутбука.', 'image' => 'https://images.pexels.com/photos/936094/pexels-photo-936094.jpeg'],
                ],
            ],
        ];
    }

    private function shopBData(): array
    {
        return [
            [
                'category' => 'Автомастера',
                'items' => [
                    ['name' => 'Диагностика двигателя', 'price' => 1500, 'description' => 'Считывание ошибок и базовая проверка узлов.', 'image' => 'https://images.pexels.com/photos/3806249/pexels-photo-3806249.jpeg'],
                    ['name' => 'Замена масла + фильтр', 'price' => 2800, 'description' => 'Расходники + работа по регламенту.', 'image' => 'https://images.pexels.com/photos/4489738/pexels-photo-4489738.jpeg'],
                    ['name' => 'Тормозные колодки (ось)', 'price' => 3400, 'description' => 'Замена передних или задних колодок.', 'image' => 'https://images.pexels.com/photos/6873088/pexels-photo-6873088.jpeg'],
                    ['name' => 'Шиномонтаж R16', 'price' => 2400, 'description' => 'Снятие, балансировка и установка комплекта.', 'image' => 'https://images.pexels.com/photos/2449452/pexels-photo-2449452.jpeg'],
                    ['name' => 'Развал-схождение', 'price' => 2200, 'description' => 'Точная настройка углов установки колес.', 'image' => 'https://images.pexels.com/photos/3807329/pexels-photo-3807329.jpeg'],
                ],
            ],
            [
                'category' => 'Эвакуаторы',
                'items' => [
                    ['name' => 'Эвакуация по городу', 'price' => 3500, 'description' => 'Погрузка и доставка авто в пределах города.', 'image' => 'https://images.pexels.com/photos/906494/pexels-photo-906494.jpeg'],
                    ['name' => 'Эвакуация за город (км)', 'price' => 90, 'description' => 'Тариф за каждый км за пределами города.', 'image' => 'https://images.pexels.com/photos/305070/pexels-photo-305070.jpeg'],
                    ['name' => 'Запуск двигателя', 'price' => 1200, 'description' => 'Выезд и помощь при севшем аккумуляторе.', 'image' => 'https://images.pexels.com/photos/13065690/pexels-photo-13065690.jpeg'],
                    ['name' => 'Подвоз топлива', 'price' => 1500, 'description' => 'Оперативная доставка топлива на место.', 'image' => 'https://images.pexels.com/photos/39284/macbook-apple-imac-computer-39284.jpeg'],
                    ['name' => 'Извлечение из кювета', 'price' => 6000, 'description' => 'Сложные случаи с лебедкой и спецтехникой.', 'image' => 'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg'],
                ],
            ],
            [
                'category' => 'Доставка еды',
                'items' => [
                    ['name' => 'Бизнес-ланч', 'price' => 590, 'description' => 'Суп, горячее и салат на выбор.', 'image' => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg'],
                    ['name' => 'Семейный набор', 'price' => 1590, 'description' => '4 порции горячего + закуски.', 'image' => 'https://images.pexels.com/photos/70497/pexels-photo-70497.jpeg'],
                    ['name' => 'Салат дня', 'price' => 320, 'description' => 'Свежие овощи и фирменная заправка.', 'image' => 'https://images.pexels.com/photos/1213710/pexels-photo-1213710.jpeg'],
                    ['name' => 'Домашний суп', 'price' => 290, 'description' => 'Большая порция, готовим ежедневно.', 'image' => 'https://images.pexels.com/photos/539451/pexels-photo-539451.jpeg'],
                    ['name' => 'Лимонад 0.5', 'price' => 170, 'description' => 'Натуральный напиток без консервантов.', 'image' => 'https://images.pexels.com/photos/96974/pexels-photo-96974.jpeg'],
                ],
            ],
            [
                'category' => 'Пицца/суши',
                'items' => [
                    ['name' => 'Пицца Пепперони 30 см', 'price' => 890, 'description' => 'Тонкое тесто, моцарелла, пепперони.', 'image' => 'https://images.pexels.com/photos/2619967/pexels-photo-2619967.jpeg'],
                    ['name' => 'Пицца 4 сыра 30 см', 'price' => 990, 'description' => 'Сливочная основа и сырный микс.', 'image' => 'https://images.pexels.com/photos/315755/pexels-photo-315755.jpeg'],
                    ['name' => 'Ролл Филадельфия', 'price' => 620, 'description' => 'Лосось, сливочный сыр, рис premium.', 'image' => 'https://images.pexels.com/photos/357756/pexels-photo-357756.jpeg'],
                    ['name' => 'Сет Классик', 'price' => 1690, 'description' => '24 ролла: хиты для компании.', 'image' => 'https://images.pexels.com/photos/2098085/pexels-photo-2098085.jpeg'],
                    ['name' => 'Вок с курицей', 'price' => 540, 'description' => 'Горячая лапша с овощами и соусом.', 'image' => 'https://images.pexels.com/photos/699953/pexels-photo-699953.jpeg'],
                ],
            ],
            [
                'category' => 'Фермеры/зелень/домашняя еда',
                'items' => [
                    ['name' => 'Микс зелени 200 г', 'price' => 240, 'description' => 'Салатный микс, руккола и шпинат.', 'image' => 'https://images.pexels.com/photos/1435904/pexels-photo-1435904.jpeg'],
                    ['name' => 'Домашний творог 500 г', 'price' => 380, 'description' => 'Натуральный фермерский творог.', 'image' => 'https://images.pexels.com/photos/248412/pexels-photo-248412.jpeg'],
                    ['name' => 'Сыр деревенский 300 г', 'price' => 520, 'description' => 'Полутвердый сыр из цельного молока.', 'image' => 'https://images.pexels.com/photos/821365/pexels-photo-821365.jpeg'],
                    ['name' => 'Овощная корзина 5 кг', 'price' => 1490, 'description' => 'Сезонные овощи с фермы, доставка в день заказа.', 'image' => 'https://images.pexels.com/photos/1656666/pexels-photo-1656666.jpeg'],
                    ['name' => 'Домашние котлеты 1 кг', 'price' => 790, 'description' => 'Полуфабрикаты ручной лепки без усилителей.', 'image' => 'https://images.pexels.com/photos/616354/pexels-photo-616354.jpeg'],
                ],
            ],
        ];
    }
}
