<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\UserController;

use App\Http\Controllers\OrderReturnController;
use App\Http\Controllers\Admin\OrderReturnController as AdminOrderReturnController;

// Breeze auth routes (sudah auto-generated)
require __DIR__.'/auth.php';

// Admin News CRUD (protected by auth middleware)
Route::middleware(['auth', 'admin'])
        ->prefix('admin')
        ->name('admin.')        // <-- tambahkan ini supaya nama route berawalan "admin."
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('news', NewsController::class)->except(['show']);
            Route::resource('products', ProductController::class)->except(['show']);
            Route::get('products/brand/{brand}', [ProductController::class, 'brandIndex'])->name('products.brand.index');
            Route::resource('contacts', ContactController::class, ['only' => ['index', 'show', 'destroy']]);
            Route::delete('/contacts-bulk', [ContactController::class, 'destroyAll'])->name('contacts.destroyAll');
            Route::resource('users', UserController::class);
            Route::resource('orders', App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show']);
            Route::patch('orders/{order}/confirm', [App\Http\Controllers\Admin\OrderController::class, 'confirm'])->name('orders.confirm');
            Route::patch('orders/{order}/ship', [App\Http\Controllers\Admin\OrderController::class, 'ship'])->name('orders.ship');
            Route::patch('orders/{order}/receive', [App\Http\Controllers\Admin\OrderController::class, 'receive'])->name('orders.receive');
            Route::patch('orders/{order}/cancel', [App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('orders.cancel');
            Route::get('returns', [AdminOrderReturnController::class, 'index'])->name('returns.index');
            Route::patch('returns/{orderReturn}/approve', [AdminOrderReturnController::class, 'approve'])->name('returns.approve');
            Route::patch('returns/{orderReturn}/reject', [AdminOrderReturnController::class, 'reject'])->name('returns.reject');
            Route::patch('returns/{orderReturn}/complete', [AdminOrderReturnController::class, 'complete'])->name('returns.complete');
            Route::get('chat', [App\Http\Controllers\Admin\MessageController::class, 'index'])->name('chat.index');
            Route::get('chat/{user}', [App\Http\Controllers\Admin\MessageController::class, 'chat'])->name('chat.show');
            Route::post('chat/send', [App\Http\Controllers\Admin\MessageController::class, 'send'])->name('chat.send');

            Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::get('inventory/incoming', [InventoryController::class, 'incoming'])->name('inventory.incoming');
            Route::post('inventory/incoming', [InventoryController::class, 'storeIncoming'])->name('inventory.incoming.store');
            Route::get('inventory/outgoing', [InventoryController::class, 'outgoing'])->name('inventory.outgoing');
            Route::post('inventory/outgoing', [InventoryController::class, 'storeOutgoing'])->name('inventory.outgoing.store');
            Route::delete('inventory/{inventoryItem}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
            Route::get('inventory/report', [InventoryController::class, 'report'])->name('inventory.report');
            Route::get('inventory/report/pdf', [InventoryController::class, 'pdfReport'])->name('inventory.report.pdf');
            Route::get('inventory/report/excel', [InventoryController::class, 'excelReport'])->name('inventory.report.excel');
        });

// Public pages (tanpa auth)
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/brands', [PageController::class, 'brands'])->name('brands.index');
Route::get('/brands/{brand}', [PageController::class, 'brandShow'])->name('brands.show');
Route::get('/produk/{product:slug}', [PageController::class, 'productShow'])->name('products.show');
Route::get('/berita', [PageController::class, 'news'])->name('news.index');
Route::get('/berita/{news:slug}', [PageController::class, 'newsShow'])->name('news.show');
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
Route::post('/kontak', [PageController::class, 'sendContact'])->name('contact.send');
Route::get('/cari', [PageController::class, 'search'])->name('search');
Route::post('/midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');

// Dashboard (setelah login - Breeze)
Route::middleware('auth')->get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('home');
})->name('dashboard');

// User cart, checkout, orders, chat
Route::middleware('auth')->group(function () {
    Route::get('/cart', [App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update/{cart}', [App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove/{cart}', [App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
    Route::get('/checkout', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/product/{product}', [App\Http\Controllers\CheckoutController::class, 'showProduct'])->name('checkout.product');
    Route::post('/checkout/direct/{product}', [App\Http\Controllers\CheckoutController::class, 'direct'])->name('checkout.direct');
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/return', [OrderReturnController::class, 'store'])->name('orders.return.store');
    Route::patch('/orders/{order}/return/bank', [OrderReturnController::class, 'updateBankDetails'])->name('orders.return.bank.update');
    Route::post('/orders/{order}/items/{orderItem}/review', [ProductReviewController::class, 'store'])->name('orders.items.review.store');
    Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{order}/sync', [PaymentController::class, 'sync'])->name('payment.sync');
    Route::post('/payment/{order}/repay', [PaymentController::class, 'repay'])->name('payment.repay');
    Route::post('/payment/{order}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('/chat', [App\Http\Controllers\MessageController::class, 'index'])->name('chat.index');
    Route::post('/chat/send', [App\Http\Controllers\MessageController::class, 'send'])->name('chat.send');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
