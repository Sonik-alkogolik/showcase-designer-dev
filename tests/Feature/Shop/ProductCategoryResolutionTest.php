<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCategoryResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveSubscription(User $user): void
    {
        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'business',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
            'price' => 1490.00,
            'payment_method' => 'test',
        ]);
    }

    private function createShop(User $user, string $name = 'Тестовый магазин'): Shop
    {
        return Shop::create([
            'user_id' => $user->id,
            'name' => $name,
            'delivery_name' => 'Курьер',
            'delivery_price' => 199,
            'notification_chat_id' => '123456789',
        ]);
    }

    public function test_store_maps_existing_category_name_to_category_id(): void
    {
        $user = User::factory()->create();
        $this->createActiveSubscription($user);
        $shop = $this->createShop($user);

        $category = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Напитки',
            'slug' => 'napitki',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/shops/{$shop->id}/products", [
            'name' => 'Кола 0.5',
            'price' => 120,
            'description' => 'Газированный напиток',
            'category' => 'Напитки',
            'in_stock' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.name', 'Кола 0.5')
            ->assertJsonPath('product.category_id', $category->id)
            ->assertJsonPath('product.category.name', 'Напитки');

        $this->assertDatabaseHas('products', [
            'shop_id' => $shop->id,
            'name' => 'Кола 0.5',
            'category_id' => $category->id,
            'category' => 'Напитки',
        ]);
    }

    public function test_store_rejects_category_id_from_another_shop(): void
    {
        $user = User::factory()->create();
        $this->createActiveSubscription($user);
        $shop = $this->createShop($user, 'Мой магазин');
        $anotherShop = $this->createShop($user, 'Чужой контекст');

        $foreignCategory = Category::create([
            'shop_id' => $anotherShop->id,
            'name' => 'Чужая категория',
            'slug' => 'chuzhaya-kategoriya',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/shops/{$shop->id}/products", [
            'name' => 'Нельзя создать',
            'price' => 250,
            'category_id' => $foreignCategory->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);

        $this->assertDatabaseMissing('products', [
            'shop_id' => $shop->id,
            'name' => 'Нельзя создать',
        ]);
    }

    public function test_update_maps_category_name_to_category_id(): void
    {
        $user = User::factory()->create();
        $this->createActiveSubscription($user);
        $shop = $this->createShop($user);

        $category = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Соусы',
            'slug' => 'sousy',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Бургер',
            'price' => 399,
            'description' => 'Тестовый товар',
            'in_stock' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/shops/{$shop->id}/products/{$product->id}", [
            'category' => 'Соусы',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.category_id', $category->id)
            ->assertJsonPath('product.category.name', 'Соусы');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
            'category' => 'Соусы',
        ]);
    }
}
