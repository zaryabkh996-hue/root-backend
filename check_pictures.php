<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \Illuminate\Support\Facades\DB::table('users')->whereNotNull('picture')->get(['id','picture']);
foreach($users as $u) {
    echo "User {$u->id}: {$u->picture}\n";
}
