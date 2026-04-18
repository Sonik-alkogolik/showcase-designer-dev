<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\Palettes\RetroPalette;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\Subscription\SubscriptionResource;
use MoonShine\MenuManager\MenuItem;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\Shop\ShopResource;
use App\MoonShine\Resources\Category\CategoryResource;
use App\MoonShine\Resources\Product\ProductResource;
use App\MoonShine\Resources\Order\OrderResource;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = RetroPalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        return [
            ...parent::menu(),
            MenuItem::make(UserResource::class, 'Пользователи'),
            MenuItem::make(SubscriptionResource::class, 'Подписки'),
            MenuItem::make(ShopResource::class, 'Магазины'),
            MenuItem::make(CategoryResource::class, 'Категории'),
            MenuItem::make(ProductResource::class, 'Товары'),
            MenuItem::make(OrderResource::class, 'Заказы'),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
