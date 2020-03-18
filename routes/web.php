<?php

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

use App\Http\Controllers\PruebaController;

//Home Route
Route::get('/', function () {
    return view('welcome');
});

//Routes API for Users
Route::post('/api/register', 'UserController@register');
Route::post('/api/login', 'UserController@login');