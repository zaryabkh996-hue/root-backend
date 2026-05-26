<?php

use App\Http\Controllers\SeederController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/seed/community-hubs', [SeederController::class, 'seedCommunityHubs']);
Route::get('/test-email', function () {

    Mail::raw('SMTP Working Test', function ($message) {
        $message->to('khanzar996@gmail.com')
                ->subject('Laravel Gmail SMTP Test');
    });

    return 'Email sent';
});
