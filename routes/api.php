<?php


use Illuminate\Support\Facades\Route;

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
Route::post('register', 'Api\AuthController@register');
Route::post('auth/login', 'Api\AuthController@login');
Route::get('auth/refresh', 'Api\AuthController@refresh');

Route::group(['middleware' => ['apiJwt'], 'prefix' => 'auth'], function() {
    Route::get('users', 'Api\UserController@index');
    Route::post('logout', 'Api\AuthController@logout');


    Route::Resource('vendaapi','Api\VendaController');
    Route::apiResource('loginapi','Api\LoginApiUserController');
    Route::apiResource('formaapi','Api\FormaPagamentoController');
    Route::apiResource('empresaapi','Api\LojasController');
    Route::apiResource('produtoapi','Api\ProdutoController');
    Route::apiResource('fluxoapi','Api\FluxoController');
    Route::apiResource('trocaapi','Api\TrocaController');
    Route::apiResource('clienteapi','Api\ClienteController');
    Route::apiResource('nfceapi','Api\NfceController');
});


