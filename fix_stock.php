<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\OutgoingInventory;
use App\Models\InventoryItem;

// Get all products
$products = Product::all();

foreach ($products as $product) {
    // Find matching inventory items
    $inventoryItems = InventoryItem::where('name', $product->name)
                                   ->where('brand_id', $product->brand_id)
                                   ->pluck('id');
                                   
    // Sum outgoing quantity
    $totalOutgoing = OutgoingInventory::whereIn('inventory_item_id', $inventoryItems)->sum('quantity');
    
    // Update product stock
    $product->stock = $totalOutgoing;
    $product->save();
    
    echo "Updated product {$product->id} stock to {$totalOutgoing}\n";
}
