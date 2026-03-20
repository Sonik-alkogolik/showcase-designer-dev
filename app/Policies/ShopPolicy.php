<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function view(User $user, Shop $shop): bool
    {
        return (int) $shop->user_id === (int) $user->id;
    }

    public function update(User $user, Shop $shop): bool
    {
        return (int) $shop->user_id === (int) $user->id;
    }

    public function delete(User $user, Shop $shop): bool
    {
        return (int) $shop->user_id === (int) $user->id;
    }
}
