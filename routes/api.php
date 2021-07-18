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
    $api->post('/auth/activity', [
        'as' => 'api.auth.activity',
        'uses' => 'App\Http\Controllers\Auth\AuthController@activity',
    ]);

    // Sidebar
    $api->get('/sidebar/popular', [
        'as' => 'api.sidebar.popular',
        'uses' => 'App\Http\Controllers\SidebarController@popular',
    ]);

    // Content
    $api->post('/content/createPost', [
        'as' => 'api.content.createPost',
        'uses' => 'App\Http\Controllers\ContentController@createPost',
    ]);
    $api->get('/content/getPosts', [
        'as' => 'api.content.getPosts',
        'uses' => 'App\Http\Controllers\ContentController@getPosts',
    ]);
    $api->get('/content/getLikes', [
        'as' => 'api.content.getLikes',
        'uses' => 'App\Http\Controllers\ContentController@getLikes',
    ]);
    $api->post('/content/likePost', [
        'as' => 'api.content.likePost',
        'uses' => 'App\Http\Controllers\ContentController@likePost',
    ]);
    $api->get('/content/getComments', [
        'as' => 'api.content.getComments',
        'uses' => 'App\Http\Controllers\ContentController@getComments',
    ]);
    $api->post('/content/createComment', [
        'as' => 'api.content.createComment',
        'uses' => 'App\Http\Controllers\ContentController@createComment',
    ]);
    $api->post('/content/uploadImage', [
        'as' => 'api.content.uploadImage',
        'uses' => 'App\Http\Controllers\ContentController@uploadImage',
    ]);
    $api->post('/content/uploadFile', [
        'as' => 'api.content.uploadFile',
        'uses' => 'App\Http\Controllers\ContentController@uploadFile',
    ]);
    $api->post('/content/reportPost', [
        'as' => 'api.content.reportPost',
        'uses' => 'App\Http\Controllers\ContentController@reportPost',
    ]);

    // User
    $api->get('/user/getBanner', [
        'as' => 'api.user.getBanner',
        'uses' => 'App\Http\Controllers\UserController@getBanner',
    ]);
    $api->post('/user/uploadBanner', [
        'as' => 'api.user.uploadBanner',
        'uses' => 'App\Http\Controllers\UserController@uploadBanner',
    ]);
    $api->post('/user/uploadAvatar', [
        'as' => 'api.user.uploadAvatar',
        'uses' => 'App\Http\Controllers\UserController@uploadAvatar',
    ]);
    $api->post('/user/setupUser', [
        'as' => 'api.user.setupUser',
        'uses' => 'App\Http\Controllers\UserController@setupUser',
    ]);
    $api->get('/user/getUserInformation', [
        'as' => 'api.user.getUserInformation',
        'uses' => 'App\Http\Controllers\UserController@getUserInformation',
    ]);
    $api->post('/user/storeCookieChoice', [
        'as' => 'api.user.storeCookieChoice',
        'uses' => 'App\Http\Controllers\UserController@storeCookieChoice',
    ]);

    // Search
    $api->get('/search', [
        'as' => 'api.search.searchQuery',
        'uses' => 'App\Http\Controllers\SearchController@searchQuery',
    ]);

    // API
    $api->group([
        'namespace' => 'App\Http\Controllers',
    ], function ($api) {
        $api->get('/', 'APIController@getIndex');
    });
});
