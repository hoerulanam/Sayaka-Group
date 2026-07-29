<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSizeVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creating_summerlabs_product_creates_30ml_and_50ml_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'SummerLabs']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'brand_id' => $brand->id,
                'name' => 'Summer Breeze',
                'description' => 'Parfum untuk pengujian.',
                'variant_prices' => [
                    '30ml' => 99000,
                    '50ml' => 139000,
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Summer Breeze')->firstOrFail();

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size' => '30ml',
            'price' => 99000,
            'stock' => 0,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size' => '50ml',
            'price' => 139000,
            'stock' => 0,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'brand_id' => $brand->id,
            'name' => 'Summer Breeze',
            'size' => '30ml',
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'brand_id' => $brand->id,
            'name' => 'Summer Breeze',
            'size' => '50ml',
        ]);
    }

    public function test_admin_creating_summerlabs_product_accepts_custom_size_variant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'SummerLabs']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'brand_id' => $brand->id,
                'name' => 'Summer Breeze Custom',
                'description' => 'Parfum dengan size tambahan.',
                'variants' => [
                    ['type' => 'size', 'value' => '30ml', 'price' => 99000],
                    ['type' => 'size', 'value' => '50ml', 'price' => 139000],
                    ['type' => 'size', 'value' => '100ml', 'price' => 199000],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Summer Breeze Custom')->firstOrFail();

        foreach (['30ml' => 99000, '50ml' => 139000, '100ml' => 199000] as $size => $price) {
            $this->assertDatabaseHas('product_variants', [
                'product_id' => $product->id,
                'size' => $size,
                'price' => $price,
                'stock' => 0,
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'brand_id' => $brand->id,
                'name' => 'Summer Breeze Custom',
                'size' => $size,
            ]);
        }
    }

    public function test_admin_can_add_custom_summerlabs_size_and_checkout_uses_that_variant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        [$product, $thirty, $fifty] = $this->createSummerLabsProduct();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'brand_id' => $product->brand_id,
                'name' => $product->name,
                'code' => $product->code,
                'description' => 'Parfum dengan size custom.',
                'variants' => [
                    ['id' => $thirty->id, 'type' => 'size', 'value' => '30ml', 'code' => $thirty->code, 'price' => 99000],
                    ['id' => $fifty->id, 'type' => 'size', 'value' => '50ml', 'code' => $fifty->code, 'price' => 139000],
                    ['type' => 'size', 'value' => '100ml', 'code' => 'SL-VAR-001-100ML', 'price' => 199000],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $hundred = ProductVariant::where('product_id', $product->id)
            ->where('size', '100ml')
            ->firstOrFail();
        $hundred->update(['stock' => 2]);

        $this->assertDatabaseHas('inventory_items', [
            'product_variant_id' => $hundred->id,
            'size' => '100ml',
        ]);

        $this->actingAs($user)
            ->get(route('brands.show', $product->brand))
            ->assertOk()
            ->assertSee('100ml')
            ->assertSee('Rp 199.000');

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), $this->checkoutPayload([
                'product_variant_id' => $hundred->id,
                'quantity' => 1,
            ]))
            ->assertRedirect();

        $this->assertSame(2, $hundred->fresh()->stock);
        $this->assertSame(3, $thirty->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $hundred->id,
            'variant_label' => '100ml',
            'quantity' => 1,
            'price' => 199000,
        ]);
    }

    public function test_admin_can_open_summerlabs_edit_form_with_dynamic_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$product] = $this->createSummerLabsProduct();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Varian Size SummerLabs')
            ->assertSee('30ml')
            ->assertSee('50ml');
    }

    public function test_customer_can_add_and_checkout_selected_summerlabs_size(): void
    {
        $user = User::factory()->create();
        [$product, $thirty, $fifty] = $this->createSummerLabsProduct();

        $this->actingAs($user)
            ->post(route('cart.add', $product), [
                'product_variant_id' => $fifty->id,
                'purchase_mode' => 'cart',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Produk ditambahkan ke keranjang.');

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $fifty->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.direct', $product), $this->checkoutPayload([
                'product_variant_id' => $fifty->id,
                'quantity' => 2,
            ]))
            ->assertRedirect();

        $this->assertSame(3, $thirty->fresh()->stock);
        $this->assertSame(4, $fifty->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $fifty->id,
            'variant_label' => '50ml',
            'quantity' => 2,
            'price' => 139000,
        ]);
    }

    public function test_modal_checkout_mode_creates_cart_row_and_redirects_to_cart_checkout(): void
    {
        $user = User::factory()->create();
        [$product, $thirty] = $this->createSummerLabsProduct();

        $response = $this->actingAs($user)
            ->post(route('cart.add', $product), [
                'product_variant_id' => $thirty->id,
                'purchase_mode' => 'checkout',
            ]);

        $cart = Cart::where('user_id', $user->id)
            ->where('product_variant_id', $thirty->id)
            ->firstOrFail();

        $response->assertRedirect(route('checkout.index', ['cart_items' => [$cart->id]]));
        $this->assertSame(1, $cart->quantity);
    }

    public function test_modal_checkout_mode_uses_existing_cart_quantity_without_incrementing_it(): void
    {
        $user = User::factory()->create();
        [$product, $thirty] = $this->createSummerLabsProduct();
        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $thirty->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('cart.add', $product), [
                'product_variant_id' => $thirty->id,
                'purchase_mode' => 'checkout',
            ])
            ->assertRedirect(route('checkout.index', ['cart_items' => [$cart->id]]));

        $this->assertSame(2, $cart->fresh()->quantity);
    }

    public function test_cart_add_rejects_invalid_purchase_mode(): void
    {
        $user = User::factory()->create();
        [$product, $thirty] = $this->createSummerLabsProduct();

        $this->actingAs($user)
            ->post(route('cart.add', $product), [
                'product_variant_id' => $thirty->id,
                'purchase_mode' => 'invalid',
            ])
            ->assertSessionHasErrors('purchase_mode');

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_customer_catalog_uses_native_popup_to_choose_summerlabs_size(): void
    {
        $user = User::factory()->create();
        [$product, $thirty, $fifty] = $this->createSummerLabsProduct();
        $fifty->update(['stock' => 0]);

        $this->actingAs($user)
            ->get(route('brands.show', $product->brand))
            ->assertOk()
            ->assertSee('data-variant-modal-open', false)
            ->assertSee('Pilih ukuran botol')
            ->assertSee('30ml')
            ->assertSee('50ml')
            ->assertSee('Rp 99.000')
            ->assertSee('Rp 139.000')
            ->assertSee('Stok 3')
            ->assertSee('Stok habis')
            ->assertSee('type="button" class="btn btn-primary catalog-variant-confirm"', false)
            ->assertSee('name="purchase_mode" value="cart"', false)
            ->assertDontSee('data-checkout-action', false)
            ->assertDontSee('form.requestSubmit()', false)
            ->assertDontSee("form.method = 'post';\n                        closeModal(modal);", false)
            ->assertDontSee('modal.dataset.variantId', false)
            ->assertSee('modal.dataset.selectedVariantId', false)
            ->assertSee('HTMLFormElement.prototype.submit.call(form);', false)
            ->assertSeeInOrder([
                '</main>',
                '<div id="catalog-variant-modal-' . $product->id . '"',
            ], false)
            ->assertDontSee('catalog-variant-field', false);

        $this->actingAs($user)
            ->get(route('checkout.product', [
                'product' => $product,
                'product_variant_id' => $thirty->id,
            ]))
            ->assertOk()
            ->assertSee('value="' . $thirty->id . '" selected', false);
    }

    public function test_outgoing_inventory_adds_sellable_stock_to_matching_size(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$product, $thirty, $fifty] = $this->createSummerLabsProduct();
        $item = InventoryItem::create([
            'brand_id' => $product->brand_id,
            'name' => $product->name,
            'code' => $fifty->code,
            'size' => '50ml',
            'stock' => 6,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.outgoing.store'), [
                'inventory_item_id' => $item->id,
                'quantity' => 2,
                'date' => '2026-06-01',
            ])
            ->assertRedirect(route('admin.inventory.outgoing'));

        $this->assertSame(4, $item->fresh()->stock);
        $this->assertSame($fifty->id, $item->fresh()->product_variant_id);
        $this->assertSame(3, $thirty->fresh()->stock);
        $this->assertSame(6, $fifty->fresh()->stock);
        $this->assertSame(9, $product->fresh()->stock);
    }

    private function createSummerLabsProduct(): array
    {
        $brand = Brand::create(['name' => 'SummerLabs']);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Variant Perfume',
            'code' => 'SL-VAR-001',
            'slug' => 'variant-perfume',
            'price' => 99000,
            'stock' => 7,
        ]);
        $thirty = ProductVariant::create([
            'product_id' => $product->id,
            'size' => '30ml',
            'code' => 'SL-VAR-001-30ML',
            'price' => 99000,
            'stock' => 3,
        ]);
        $fifty = ProductVariant::create([
            'product_id' => $product->id,
            'size' => '50ml',
            'code' => 'SL-VAR-001-50ML',
            'price' => 139000,
            'stock' => 4,
        ]);

        return [$product, $thirty, $fifty];
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'quantity' => 1,
            'payment_method' => 'midtrans_snap',
            'destination_city' => 'jakarta',
            'shipping_address' => 'Jl. Testing Varian No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 12000,
        ], $overrides);
    }
}
