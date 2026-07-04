<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$ok = \Auth::attempt(['email' => 'admin@sayaka.com', 'password' => 'password123']);
echo $ok ? "OK\n" : "FAIL\n";
