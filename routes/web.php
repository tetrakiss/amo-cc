<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\amoLeads;

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/amocode/refresh', [amoLeads::class, 'refreshToken']);
Route::get('/amocode/testupdate', [amoLeads::class, 'testupdate']);
Route::get('/amocode/getorder', [amoLeads::class, 'getorder']);
Route::get('/amocode/addLead', [amoLeads::class, 'addLead']);
Route::get('/amocode/addone', [amoLeads::class, 'addone']);





 
Route::get('/amocode/get', [amoLeads::class, 'getauthcode']);