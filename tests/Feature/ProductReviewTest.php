<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_review_for_received_order_item(): void
    {
        [$user, $product, $order, $item] = $this->createOrderItemForReview();

        $this->actingAs($user)
            ->post(route('orders.items.review.store', [$order, $item]), [
                'rating' => 5,
                'review' => 'Produk rapi dan mudah dipakai.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Review produk berhasil dikirim.');

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'rating' => 5,
            'review' => 'Produk rapi dan mudah dipakai.',
        ]);
    }

    public function test_customer_cannot_review_unfinished_order(): void
    {
        [$user, , $order, $item] = $this->createOrderItemForReview([
            'status' => 'shipped',
        ]);

        $this->actingAs($user)
            ->post(route('orders.items.review.store', [$order, $item]), [
                'rating' => 5,
                'review' => 'Belum selesai.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Review hanya dapat diberikan setelah pesanan selesai.');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_customer_cannot_review_another_users_order(): void
    {
        [$user, , $order, $item] = $this->createOrderItemForReview();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post(route('orders.items.review.store', [$order, $item]), [
                'rating' => 4,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_customer_cannot_review_item_from_another_order(): void
    {
        [$user, , $order] = $this->createOrderItemForReview();
        [, , , $otherItem] = $this->createOrderItemForReview([], $user);

        $this->actingAs($user)
            ->post(route('orders.items.review.store', [$order, $otherItem]), [
                'rating' => 4,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_customer_can_update_review_without_creating_duplicate(): void
    {
        [$user, , $order, $item] = $this->createOrderItemForReview();

        $this->actingAs($user)
            ->post(route('orders.items.review.store', [$order, $item]), [
                'rating' => 3,
                'review' => 'Cukup baik.',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('orders.items.review.store', [$order, $item]), [
                'rating' => 5,
                'review' => 'Setelah dicoba lagi hasilnya sangat baik.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Review produk berhasil diperbarui.');

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'review' => 'Setelah dicoba lagi hasilnya sangat baik.',
        ]);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        [$user, , $order, $item] = $this->createOrderItemForReview();

        $this->actingAs($user)
            ->post(route('orders.items.review.store', [$order, $item]), [
                'rating' => 6,
                'review' => 'Rating tidak valid.',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_order_detail_shows_review_form_only_after_order_is_received(): void
    {
        [$user, , $receivedOrder] = $this->createOrderItemForReview();
        [, , $pendingOrder] = $this->createOrderItemForReview([
            'status' => 'pending',
        ], $user);

        $this->actingAs($user)
            ->get(route('orders.show', $receivedOrder))
            ->assertOk()
            ->assertSee('Kirim Review')
            ->assertSee('product-review-form', false)
            ->assertSee('product-review-star-picker', false);

        $this->actingAs($user)
            ->get(route('orders.show', $pendingOrder))
            ->assertOk()
            ->assertDontSee('Kirim Review')
            ->assertDontSee('product-review-form', false);
    }

    public function test_catalog_shows_product_rating_summary(): void
    {
        [$user, $product, $order, $item] = $this->createOrderItemForReview();

        ProductReview::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'rating' => 4,
            'review' => 'Ringkas dan membantu.',
        ]);

        $this->get(route('brands.index'))
            ->assertOk()
            ->assertSee('Reviewable Cleaner')
            ->assertSee('product-star-rating', false)
            ->assertSee('4,0/5')
            ->assertSee('1 review');
    }

    private function createOrderItemForReview(array $orderOverrides = [], ?User $user = null): array
    {
        $user ??= User::factory()->create();
        $brand = Brand::create(['name' => 'Review Test Brand ' . uniqid()]);
        $product = Product::create([
            'brand_id' => $brand->id,
            'name' => 'Reviewable Cleaner',
            'slug' => 'reviewable-cleaner-' . uniqid(),
            'price' => 50000,
            'stock' => 10,
        ]);
        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'total' => 50000,
            'status' => 'received',
            'payment_method' => 'midtrans_snap',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Review No. 1, Jakarta',
            'phone' => '081234567890',
            'shipping_carrier' => 'JNE',
            'shipping_service' => 'Reguler (REG)',
            'shipping_cost' => 0,
        ], $orderOverrides));
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 50000,
        ]);

        return [$user, $product, $order, $item];
    }
}
