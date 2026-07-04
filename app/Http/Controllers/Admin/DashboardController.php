<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Contacts;
use App\Models\IncomingInventory;
use App\Models\InventoryItem;
use App\Models\Message;
use App\Models\News;
use App\Models\Order;
use App\Models\OutgoingInventory;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'news' => News::count(),
            'products' => Product::count(),
            'brands' => Brand::count(),
            'product_stock' => (int) Product::sum('stock'),
            'inventory_stock' => (int) InventoryItem::sum('stock'),
            'orders' => Order::count(),
            'paid_orders' => Order::where('payment_status', 'paid')->count(),
            'revenue' => (float) Order::where('payment_status', 'paid')->sum('total'),
            'users' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'contacts' => Contacts::count(),
            'unread_messages' => Message::whereNull('read_at')->count(),
            'incoming_items' => (int) IncomingInventory::sum('quantity'),
            'outgoing_items' => (int) OutgoingInventory::sum('quantity'),
        ];

        $orderStatusLabels = [
            'pending' => 'Pending',
            'confirmed' => 'Dikonfirmasi',
            'shipped' => 'Dikirim',
            'received' => 'Diterima',
        ];

        $orderStatuses = collect($orderStatusLabels)->map(function ($label, $status) {
            return [
                'label' => $label,
                'status' => $status,
                'count' => Order::where('status', $status)->count(),
            ];
        })->values();

        $productsByBrand = Brand::withCount('products')
            ->withSum('products as stock_total', 'stock')
            ->orderBy('name')
            ->get();

        $lowStockProducts = Product::with('brand')
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(6)
            ->get();

        $latestOrders = Order::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $latestNews = News::latest('published_at')
            ->limit(5)
            ->get();

        $latestContacts = Contacts::latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'orderStatuses',
            'productsByBrand',
            'lowStockProducts',
            'latestOrders',
            'latestNews',
            'latestContacts'
        ));
    }
}
