<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$photos = \Illuminate\Support\Facades\DB::table('journey_photos')->get(['id','user_id','url']);
echo "Journey Photos:\n";
foreach($photos as $p) {
    echo "Photo {$p->id} (User {$p->user_id}): {$p->url}\n";
}
