<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OrderDraftApiTest extends TestCase
{
    use RefreshDatabase;

    private function createShop(User $user, string $name = 'Тестовый магазин', float $deliveryPrice = 199): Shop
    {
        return Shop::create([
            'user_id' => $user->id,
            'name' => $name,
            'delivery_name' => 'Курьер',
            'delivery_price' => $deliveryPrice,
            'notification_chat_id' => '123456789',
        ]);
    }

    public function test_public_api_creates_order_draft_and_uses_server_prices(): void
    {
        $owner = User::factory()->create();
        $shop = $this->createShop($owner, 'Магазин 1', 150);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Пицца',
            'price' => 500,
            'description' => 'Большая пицца',
            'in_stock' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван Петров',
            'phone' => '+79991234567',
            'items' => [
                [
                    'id' => $product->id,
                    'name' => 'Подмена имени',
                    'price' => 1,
                    'quantity' => 2,
                ],
            ],
            'create_payment' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Черновик заказа создан')
            ->assertJsonPath('order.shop_id', $shop->id)
            ->assertJsonPath('order.customer_name', 'Иван Петров')
            ->assertJsonPath('order.total', '1000.00')
            ->assertJsonPath('amounts.subtotal', 1000)
            ->assertJsonPath('amounts.delivery', 150)
            ->assertJsonPath('amounts.total', 1150)
            ->assertJsonPath('order.items.0.id', $product->id)
            ->assertJsonPath('order.items.0.name', 'Пицца')
            ->assertJsonPath('order.items.0.price', 500)
            ->assertJsonPath('order.items.0.quantity', 2)
            ->assertJsonPath('order.items.0.line_total', 1000);

        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван Петров',
            'status' => 'pending',
            'total' => 1000.00,
            'delivery_price' => 150.00,
        ]);
    }

    public function test_public_api_rejects_items_from_another_shop(): void
    {
        $owner = User::factory()->create();
        $shop = $this->createShop($owner, 'Магазин 1', 150);
        $anotherShop = $this->createShop($owner, 'Магазин 2', 120);

        $foreignProduct = Product::create([
            'shop_id' => $anotherShop->id,
            'name' => 'Чужой товар',
            'price' => 300,
            'in_stock' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван Петров',
            'phone' => '+79991234567',
            'items' => [
                [
                    'id' => $foreignProduct->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.items.0', 'Некорректный состав корзины.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_public_api_rejects_out_of_stock_items(): void
    {
        $owner = User::factory()->create();
        $shop = $this->createShop($owner, 'Магазин 1', 150);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Нет в наличии',
            'price' => 300,
            'in_stock' => false,
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван Петров',
            'phone' => '+79991234567',
            'items' => [
                [
                    'id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_payment_requires_yookassa_credentials(): void
    {
        Config::set('services.yookassa.shop_id', '');
        Config::set('services.yookassa.secret_key', '');

        $owner = User::factory()->create();
        $shop = $this->createShop($owner, 'Магазин 1', 150);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Товар',
            'price' => 300,
            'in_stock' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван Петров',
            'phone' => '+79991234567',
            'items' => [
                [
                    'id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'create_payment' => true,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.create_payment.0', 'Невозможно создать платёж без ключей ЮKassa.');

        // Заказ остаётся в статусе pending как черновик для повторной попытки оплаты.
        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван Петров',
            'status' => 'pending',
            'total' => 300.00,
        ]);
    }
}
