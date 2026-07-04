<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::all();
echo "Products:\n";
foreach($products as $p) {
    echo "ID: {$p->id}, Name: {$p->name}, Stock: {$p->stock}\n";
}

$inventoryItems = App\Models\InventoryItem::all();
echo "\nInventory Items:\n";
foreach($inventoryItems as $item) {
    echo "ID: {$item->id}, Name: {$item->name}, Stock: {$item->stock}\n";
}

$outgoings = App\Models\OutgoingInventory::all();
echo "\nOutgoings:\n";
foreach($outgoings as $out) {
    echo "ID: {$out->id}, InvID: {$out->inventory_item_id}, Qty: {$out->quantity}\n";
}
