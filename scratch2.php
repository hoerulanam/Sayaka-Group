<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = App\Models\Product::find(2);
echo "Before: {$product->stock}\n";

$product->increment('stock', 5);
$product = App\Models\Product::find(2);
echo "After: {$product->stock}\n";
