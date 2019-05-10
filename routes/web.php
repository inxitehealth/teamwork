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

Route::get('/', function () {
    return view('welcome');
});

// if you do have a custom domain:
\TeamWorkPm\Auth::set(env('TEAMWORK_URL'), env('TEAMWORK_API_KEY'));

Auth::routes(['register' => false]);
Route::get('/home', 'HomeController@index')->middleware('teamwork_auth')->name('home');

Route::post('/sendReport', 'HomeController@sendReport');
