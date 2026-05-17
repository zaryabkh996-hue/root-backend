<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email', function () {

    Mail::raw('SMTP Working Test', function ($message) {
        $message->to('khanzar996@gmail.com')
                ->subject('Laravel Gmail SMTP Test');
    });

    return 'Email sent';
});
