<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\Subscription\SubscriptionResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\Shop\ShopResource;
use App\MoonShine\Resources\Category\CategoryResource;
use App\MoonShine\Resources\Product\ProductResource;
use App\MoonShine\Resources\Order\OrderResource;
use App\MoonShine\Resources\SubscriptionPayment\SubscriptionPaymentResource;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                SubscriptionResource::class,
                UserResource::class,
                ShopResource::class,
                CategoryResource::class,
                ProductResource::class,
                OrderResource::class,
                SubscriptionPaymentResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ])
        ;
    }
}
