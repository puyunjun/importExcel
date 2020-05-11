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
Route::get('/tools/deal-old-data','Tools\DealDatasController@dealOldData')->name('tools.deal-old-data');
Route::any('/tools/upload-index','Tools\DealDatasController@index')->name('tools.upload-index');
