<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$api = $app->make(Dingo\Api\Routing\Router::class);

$api->version('v1', function ($api) {

    // Auth JWT
    $api->post('/auth/login', [
        'as' => 'api.auth.login',
        'uses' => 'App\Http\Controllers\Auth\AuthController@postLogin',
    ]);
    $api->group([
        'namespace' => 'App\Http\Controllers\Auth',
        'middleware' => 'api.auth'
    ], function ($api) {
        $api->get('/auth/user', 'AuthController@getUser');
        $api->patch('/auth/refresh', 'AuthController@patchRefresh');
        $api->delete('/auth/invalidate', 'AuthController@deleteInvalidate');
    });
    $api->post('/auth/register', [
        'as' => 'api.auth.register',
        'uses' => 'App\Http\Controllers\Auth\AuthController@postRegister',
    ]);
    $api->post('/auth/reset', [
        'as' => 'api.auth.reset',
        'uses' => 'App\Http\Controllers\Auth\AuthController@postReset',
    ]);
    $api->post('/auth/reset/2', [
        'as' => 'api.auth.reset_2',
        'uses' => 'App\Http\Controllers\Auth\AuthController@postReset2',
    ]);
    $api->post('/auth/reset/3', [
        'as' => 'api.auth.reset_3',
        'uses' => 'App\Http\Controllers\Auth\AuthController@postReset3',
    ]);
    $api->get('/sidebar/popular', [
        'as' => 'api.sidebar.popular',
        'uses' => 'App\Http\Controllers\SidebarController@popular',
    ]);
    $api->post('/content/createPost', [
        'as' => 'api.content.createPost',
        'uses' => 'App\Http\Controllers\ContentController@createPost',
    ]);
    $api->get('/content/getPosts', [
        'as' => 'api.content.getPosts',
        'uses' => 'App\Http\Controllers\ContentController@getPosts',
    ]);


    // API
    $api->group([
        'namespace' => 'App\Http\Controllers',
        'middleware' => 'api.auth',
    ], function ($api) {
        $api->get('/', 'APIController@getIndex');
    });
});
