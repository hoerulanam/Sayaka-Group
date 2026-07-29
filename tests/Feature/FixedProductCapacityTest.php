<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ProductVariantService;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedProductCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_fixed_capacity_product_without_duplicating_inventory_item(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'SummerShoesCare']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'brand_id' => $brand->id,
                'name' => 'Lem Super Kuat',
                'price' => 25000,
                'size' => '100ml',
                'description' => 'Lem perbaikan sepatu.',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Lem Super Kuat')->firstOrFail();
        $inventoryItem = InventoryItem::where('name', 'Lem Super Kuat')->firstOrFail();

        $this->assertSame('100ml', $product->size);
        $this->assertSame('100ml', $inventoryItem->size);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'brand_id' => $brand->id,
                'name' => 'Lem Super Kuat Premium',
                'price' => 27000,
                'size' => '120ml',
                'description' => 'Lem perbaikan sepatu premium.',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Lem Super Kuat Premium',
            'size' => '120ml',
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $inventoryItem->id,
            'name' => 'Lem Super Kuat Premium',
            'size' => '120ml',
        ]);
        $this->assertDatabaseCount('inventory_items', 1);
    }

    public function test_outgoing_inventory_for_fixed_capacity_product_adds_normal_product_stock(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'SummerShoesCare']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Lem Super Kuat',
            'code' => 'SSC-LEM-001',
            'size' => '100ml',
            'slug' => 'lem-super-kuat',
            'price' => 25000,
            'stock' => 0,
        ]);
        $inventoryItem = InventoryItem::create([
            'brand_id' => $brand->id,
            'name' => 'Lem Super Kuat',
            'code' => 'SSC-LEM-001',
            'size' => '100ml',
            'stock' => 6,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.outgoing.store'), [
                'inventory_item_id' => $inventoryItem->id,
                'quantity' => 2,
                'date' => '2026-06-02',
            ])
            ->assertRedirect(route('admin.inventory.outgoing'));

        $this->assertSame(4, $inventoryItem->fresh()->stock);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_fixed_capacity_is_visible_and_checkout_saves_order_snapshot(): void
    {
        $user = User::factory()->create();
        $product = $this->createShoePaint();
        $brand = $product->brand;
        $hitam = $this->colorVariant($product, 'Hitam');

        $this->actingAs($user)
            ->get(route('brands.show', $brand))
            ->assertOk()
            ->assertSee('Ukuran: 60ml')
            ->assertSee('data-variant-modal-open', false)
            ->assertSee('Pilih warna cat sepatu')
            ->assertSee('Stok 5')
            ->assertSee('Stok habis')
            ->assertSee('type="button" class="btn btn-primary catalog-variant-confirm"', false)
            ->assertSeeInOrder([
                '</main>',
                '<div id="catalog-variant-modal-' . $product->id . '"',
            ], false);

        $this->actingAs($user)
            ->post(route('cart.add', $product), ['product_variant_id' => $hitam->id])
            ->assertSessionHasNoErrors();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Ukuran:')
            ->assertSee('60ml')
            ->assertSee('Warna:')
            ->assertSee('Hitam');

        $this->actingAs($user)
            ->get(route('checkout.index', ['cart_items' => [$cart->id]]))
            ->assertOk()
            ->assertSee('Ukuran 60ml')
            ->assertSee('Warna Hitam');

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), [
                ...$this->checkoutPayload(),
                'product_variant_id' => $hitam->id,
            ])
            ->assertRedirect();

        $order = Order::where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $hitam->id,
            'variant_label' => '60ml',
            'selected_color' => 'Hitam',
        ]);

        $product->update(['size' => '120ml']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Ukuran 60ml')
            ->assertSee('Warna Hitam')
            ->assertDontSee('Ukuran 120ml');

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Warna: Hitam');
    }

    public function test_shoe_paint_direct_checkout_requires_a_valid_color_variant(): void
    {
        $user = User::factory()->create();
        $product = $this->createShoePaint();
        $payload = $this->checkoutPayload();

        $this->actingAs($user)
            ->get(route('checkout.product', $product))
            ->assertOk()
            ->assertSee('Warna Cat Sepatu')
            ->assertSee('Hitam')
            ->assertSee('Biru');

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), $payload)
            ->assertSessionHasErrors('product_variant_id');

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), [
                ...$payload,
                'product_variant_id' => 999999,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shoe_paint_colors_create_separate_cart_rows_and_checkout_selected_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createShoePaint();
        $hitam = $this->colorVariant($product, 'Hitam');
        $putih = $this->colorVariant($product, 'Putih');

        $this->actingAs($user)
            ->post(route('cart.add', $product), ['product_variant_id' => $hitam->id])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('cart.add', $product), ['product_variant_id' => $putih->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('carts', 2);
        $hitamCart = Cart::where('product_variant_id', $hitam->id)->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('cart.update', $hitamCart), ['quantity' => 6, 'action' => 'set'])
            ->assertUnprocessable()
            ->assertJsonPath('stock', 5);

        $this->actingAs($user)
            ->get(route('checkout.index', ['cart_items' => [$hitamCart->id]]))
            ->assertOk()
            ->assertSee('Warna Hitam');

        $this->actingAs($user)
            ->post(route('checkout.store'), $this->cartCheckoutPayload($hitamCart))
            ->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $hitam->id,
            'selected_color' => 'Hitam',
            'quantity' => 1,
        ]);
        $this->assertSame(5, $hitam->fresh()->stock);
        $this->assertSame(4, $putih->fresh()->stock);
    }

    public function test_admin_can_add_custom_shoe_paint_color_and_checkout_uses_that_variant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $product = $this->createShoePaint();
        $hitam = $this->colorVariant($product, 'Hitam');

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'code' => Product::SHOE_PAINT_CODE,
                'price' => 35000,
                'size' => '60ml',
                'description' => 'Cat sepatu dengan warna tambahan.',
                'variants' => [
                    ['type' => 'color', 'value' => 'Kuning', 'code' => 'SSC-CAT-002-KUNING'],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $kuning = ProductVariant::where('product_id', $product->id)
            ->where('color', 'Kuning')
            ->firstOrFail();
        $kuningItem = InventoryItem::where('product_variant_id', $kuning->id)->firstOrFail();
        $kuningItem->update(['stock' => 3]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.outgoing.store'), [
                'inventory_item_id' => $kuningItem->id,
                'quantity' => 2,
                'date' => '2026-06-02',
            ])
            ->assertRedirect(route('admin.inventory.outgoing'));

        $this->actingAs($user)
            ->get(route('brands.show', $product->brand))
            ->assertOk()
            ->assertSee('Kuning')
            ->assertSee('Stok 2');

        $this->actingAs($user)
            ->post(route('cart.add', $product), ['product_variant_id' => $kuning->id])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), [
                ...$this->checkoutPayload(),
                'product_variant_id' => $kuning->id,
            ])
            ->assertRedirect();

        $this->assertSame(2, $kuning->fresh()->stock);
        $this->assertSame(5, $hitam->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $kuning->id,
            'variant_label' => '60ml',
            'selected_color' => 'Kuning',
            'price' => 35000,
        ]);
    }

    public function test_admin_can_open_shoe_paint_edit_form_with_dynamic_colors(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->createShoePaint();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Varian Warna Cat Sepatu')
            ->assertSee('Hitam')
            ->assertSee('Biru');
    }

    public function test_shoe_paint_inventory_is_managed_per_color(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->createShoePaint([]);
        $hitam = $this->colorVariant($product, 'Hitam');
        $putih = $this->colorVariant($product, 'Putih');
        $hitamItem = InventoryItem::where('color', 'Hitam')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.inventory.incoming.store'), [
                'inventory_item_id' => $hitamItem->id,
                'quantity' => 6,
                'date' => '2026-06-02',
            ])
            ->assertRedirect(route('admin.inventory.incoming'));

        $this->actingAs($admin)
            ->post(route('admin.inventory.outgoing.store'), [
                'inventory_item_id' => $hitamItem->id,
                'quantity' => 2,
                'date' => '2026-06-02',
            ])
            ->assertRedirect(route('admin.inventory.outgoing'));

        $this->assertSame(4, $hitamItem->fresh()->stock);
        $this->assertSame(2, $hitam->fresh()->stock);
        $this->assertSame(0, $putih->fresh()->stock);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_new_shoe_paint_inventory_requires_a_valid_color(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->createShoePaint([]);
        $payload = [
            'product_name' => $product->name,
            'product_code' => Product::SHOE_PAINT_CODE,
            'brand_id' => $product->brand_id,
            'quantity' => 3,
            'date' => '2026-06-02',
        ];

        $this->actingAs($admin)
            ->post(route('admin.inventory.incoming.store'), $payload)
            ->assertSessionHasErrors('color');

        $this->actingAs($admin)
            ->post(route('admin.inventory.incoming.store'), [...$payload, 'color' => 'Merah'])
            ->assertRedirect(route('admin.inventory.incoming'));

        $this->assertDatabaseHas('inventory_items', [
            'code' => 'SSC-CAT-002-MERAH',
            'size' => '60ml',
            'color' => 'Merah',
            'stock' => 3,
        ]);
    }

    public function test_other_products_can_checkout_without_a_color(): void
    {
        $user = User::factory()->create();
        $brand = Brand::create(['name' => 'SummerShoesCare']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Lem Super Kuat',
            'code' => 'SSC-LEM-001',
            'size' => '100ml',
            'slug' => 'lem-super-kuat',
            'price' => 25000,
            'stock' => 3,
        ]);
        $payload = $this->checkoutPayload();

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'selected_color' => null,
        ]);
    }

    public function test_product_seeder_uses_sku_identity_for_fixed_capacity_products(): void
    {
        $this->seed(BrandSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(ProductSeeder::class);

        $this->assertSame(1, Product::where('code', 'SSC-LEM-001')->count());
        $this->assertDatabaseHas('products', [
            'code' => 'SSC-LEM-001',
            'name' => 'Lem Super Kuat',
            'slug' => 'lem-super-kuat',
            'size' => '100ml',
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'code' => 'SSC-CAT-002-HITAM',
            'name' => 'Cat Sepatu',
            'size' => '60ml',
            'color' => 'Hitam',
        ]);
        $this->assertSame(5, ProductVariant::whereHas('product', fn ($query) => $query->where('code', Product::SHOE_PAINT_CODE))->count());
    }

    private function checkoutPayload(): array
    {
        return [
            'quantity' => 1,
            'payment_method' => 'midtrans_snap',
            'destination_city' => 'jakarta',
            'shipping_address' => 'Jl. Testing Kapasitas No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ];
    }

    private function cartCheckoutPayload(Cart $cart): array
    {
        return [
            'cart_items' => [$cart->id],
            'payment_method' => 'midtrans_snap',
            'destination_city' => 'jakarta',
            'shipping_address' => 'Jl. Testing Kapasitas No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ];
    }

    private function createShoePaint(array $stocks = ['Hitam' => 5, 'Putih' => 4]): Product
    {
        $brand = Brand::create(['name' => 'SummerShoesCare']);

        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Cat Sepatu',
            'code' => 'SSC-CAT-002',
            'size' => '60ml',
            'slug' => 'cat-sepatu',
            'price' => 35000,
            'stock' => 0,
        ]);

        $variants = app(ProductVariantService::class)->ensureForProduct($product);

        foreach ($variants as $variant) {
            $variant->update(['stock' => $stocks[$variant->color] ?? 0]);
        }

        app(ProductVariantService::class)->syncProductStock($product);

        return $product->fresh(['brand', 'variants']);
    }

    private function colorVariant(Product $product, string $color): ProductVariant
    {
        return $product->variants->firstWhere('color', $color)
            ?? $product->variants()->where('color', $color)->firstOrFail();
    }
}
