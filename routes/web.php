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

Route::match(['get', 'post'], '/botman', 'BotManController@handle');
Route::get('/botman/tinker', 'BotManController@tinker');

Route::get('/test', function() {
    $crawler = Goutte::request('GET', 'http://www.tmesa.com/index.asp?lang=ca&proces=horarisCalcul');
    $crawler->filter('select#IdLineaMenuHorari option')->each(function ($node) {
        dump($node->text());
    });
    return view('welcome');
});