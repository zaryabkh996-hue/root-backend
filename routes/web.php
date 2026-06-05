<?php

use App\Http\Controllers\SeederController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

