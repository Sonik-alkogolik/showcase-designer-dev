<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductImportLimitsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function createActiveSubscription(User $user, string $plan): void
    {
        Subscription::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
            'price' => $plan === 'business' ? 500.00 : 0.00,
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

    private function createProducts(Shop $shop, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Product::create([
                'shop_id' => $shop->id,
                'name' => "Товар {$i}",
                'price' => 100 + $i,
                'description' => "Описание {$i}",
                'in_stock' => true,
            ]);
        }
    }

    private function createXlsxUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $columns) {
            foreach ($columns as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $tmpDir = storage_path('framework/testing');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $tmpPath = tempnam($tmpDir, 'xlsx_');
        $xlsxPath = $tmpPath . '.xlsx';
        rename($tmpPath, $xlsxPath);
        $this->temporaryFiles[] = $xlsxPath;

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $xlsxPath,
            'products.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_starter_plan_cannot_import_excel(): void
    {
        $user = User::factory()->create();
        $this->createActiveSubscription($user, 'starter');
        $shop = $this->createShop($user);

        Sanctum::actingAs($user);

        $file = $this->createXlsxUpload([
            ['Название', 'Цена'],
            ['Товар A', 120],
        ]);

        $response = $this->post(
            "/api/shops/{$shop->id}/import",
            [
                'file' => $file,
                'mapping' => json_encode([
                    'name' => 0,
                    'price' => 1,
                ]),
            ],
            ['Accept' => 'application/json']
        );

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('can_import_excel', false)
            ->assertJsonPath('limit', 20)
            ->assertJsonPath('current_count_before_import', 0)
            ->assertJsonPath('available_slots_before_import', 20);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_business_import_is_limited_by_available_slots_for_xlsx(): void
    {
        $user = User::factory()->create();
        $this->createActiveSubscription($user, 'business');
        $shop = $this->createShop($user);
        $this->createProducts($shop, 198);

        Sanctum::actingAs($user);

        $rows = [
            ['Название', 'Цена', 'Описание', 'Категория', 'Наличие'],
        ];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = ["Импорт {$i}", 100 + $i, "Описание {$i}", 'Импорт', 1];
        }

        $file = $this->createXlsxUpload($rows);

        $response = $this->post(
            "/api/shops/{$shop->id}/import",
            [
                'file' => $file,
                'mapping' => json_encode([
                    'name' => 0,
                    'price' => 1,
                    'description' => 2,
                    'category' => 3,
                    'in_stock' => 4,
                ]),
            ],
            ['Accept' => 'application/json']
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('limit', 200)
            ->assertJsonPath('current_count_before_import', 198)
            ->assertJsonPath('available_slots_before_import', 2)
            ->assertJsonPath('success_count', 2)
            ->assertJsonPath('imported_count', 2)
            ->assertJsonPath('skipped_due_to_limit', 8)
            ->assertJsonPath('total_rows', 10);

        $this->assertSame(200, $shop->products()->count());
    }
}

