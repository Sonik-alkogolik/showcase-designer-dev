<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductSliderFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_set_show_in_slider_on_store_and_update(): void
    {
        [$user, $shop] = $this->makeUserWithShop();
        Sanctum::actingAs($user);

        $create = $this->postJson("/api/shops/{$shop->id}/products", [
            'name' => 'Слайдер товар',
            'price' => 1000,
            'in_stock' => true,
            'show_in_slider' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('product.show_in_slider', true);

        $productId = (int) $create->json('product.id');

        $this->putJson("/api/shops/{$shop->id}/products/{$productId}", [
            'show_in_slider' => false,
        ])->assertOk()
            ->assertJsonPath('product.show_in_slider', false);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'show_in_slider' => 0,
        ]);
    }

    public function test_public_catalog_returns_show_in_slider_flag(): void
    {
        [$user, $shop] = $this->makeUserWithShop();

        Product::create([
            'shop_id' => $shop->id,
            'name' => 'Баннерный товар',
            'price' => 1200,
            'in_stock' => true,
            'show_in_slider' => true,
        ]);

        Product::create([
            'shop_id' => $shop->id,
            'name' => 'Обычный товар',
            'price' => 900,
            'in_stock' => true,
            'show_in_slider' => false,
        ]);

        $response = $this->getJson("/api/shops/{$shop->id}/products/public");

        $response->assertOk();
        $products = $response->json('products');

        $this->assertIsArray($products);
        $banner = collect($products)->firstWhere('name', 'Баннерный товар');
        $normal = collect($products)->firstWhere('name', 'Обычный товар');

        $this->assertNotNull($banner);
        $this->assertNotNull($normal);
        $this->assertTrue((bool) $banner['show_in_slider']);
        $this->assertFalse((bool) $normal['show_in_slider']);
    }

    public function test_owner_can_upload_product_image_on_update(): void
    {
        Storage::fake('public');
        [$user, $shop] = $this->makeUserWithShop();
        Sanctum::actingAs($user);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Товар с фото',
            'price' => 300,
            'in_stock' => true,
        ]);

        $file = UploadedFile::fake()->image('product.jpg', 600, 600);

        $response = $this->postJson("/api/shops/{$shop->id}/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Товар с фото',
            'price' => 300,
            'image_file' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $product->refresh();
        $this->assertNotNull($product->image);
        $this->assertStringStartsWith('/storage/products/', (string) $product->image);

        $relativePath = ltrim(str_replace('/storage/', '', (string) $product->image), '/');
        Storage::disk('public')->assertExists($relativePath);
    }

    private function makeUserWithShop(): array
    {
        $user = User::factory()->create([
            'telegram_id' => 555001,
            'telegram_username' => 'slider_test_user',
            'telegram_linked_at' => now(),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'starter',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
            'price' => 490.00,
            'payment_method' => 'test',
        ]);

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => 'Slider shop',
            'delivery_name' => 'Курьер',
            'delivery_price' => 100,
        ]);

        return [$user, $shop];
    }
}
