<?php

use App\Http\Controllers\Zoom\MeetingViewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ MeetingViewController::class, 'index'])->name('index');

Route::post('/create', [ MeetingViewController::class, 'create'])->name('create');
