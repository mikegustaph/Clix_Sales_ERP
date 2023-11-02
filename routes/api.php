<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('fetch-data-general', 'DemoAutoUpdateController@fetchDataGeneral')->name('fetch-data-general');
Route::get('fetch-data-upgrade', 'DemoAutoUpdateController@fetchDataForAutoUpgrade')->name('data-read');
Route::get('fetch-data-bugs', 'DemoAutoUpdateController@fetchDataForBugs')->name('fetch-data-bugs');
