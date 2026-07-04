<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$users = \DB::table('users')->select('id','email','password','created_at')->get()->toArray();
echo json_encode($users, JSON_PRETTY_PRINT) . "\n";
