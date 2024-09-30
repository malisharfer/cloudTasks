<?php

require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::connection()->getPdo(); 
    echo DB::table('users')->get();   
    
} catch (Exception $e) {
    echo 'Database connection error: '.$e->getMessage();
}