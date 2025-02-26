<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\TransactionController;

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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::middleware(['auth:api', 'throttle:60,1'])
    ->group(function () {
        Route::get('/user', [AuthController::class, 'getUser']);
        Route::get('/wallet', [WalletController::class, 'getWallet']);
        Route::get('/check_balance', [WalletController::class, 'balance']);
        Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
        Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
        Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
        Route::get('/wallet/transactions', [TransactionController::class, 'history']);
    });
