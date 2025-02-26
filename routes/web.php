<?php


use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Dashboard\Admin;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */

Route::get('cls', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    return "Cache is cleared";
});

Route::get('symlink', function () {
    Artisan::call('storage:link');
    return "Sym link created";
});

Route::get('migrate-tables', function () {
    Artisan::call('migrate', ['--force' => true]);
    return "Tables migrated";
});

Route::get('/set-default-password', function () {
    User::where('id', '>', 0)->update([
        'password' => Hash::make(config('app.default_user_password')),
    ]);
    echo "Done, set default password for all users";
});
