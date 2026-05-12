<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LibraryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/me', [AuthController::class, 'me']);
Route::post('/refresh', [AuthController::class, 'refresh']);

// Public Library Routes (Customer View)
Route::get('/libraries', [LibraryController::class, 'index']);
Route::get('/libraries/{id}', [LibraryController::class, 'show']);

// Admin CRUD operations
Route::post('/libraries', [LibraryController::class, 'store']);
Route::put('/libraries/{id}', [LibraryController::class, 'update']);
Route::delete('/libraries/{id}', [LibraryController::class, 'destroy']);
Route::get('/admin/libraries', [LibraryController::class, 'adminList']);

