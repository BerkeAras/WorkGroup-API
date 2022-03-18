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
    $api->get('/auth/checkActivation', [
        'as' => 'api.auth.checkActivation',
        'uses' => 'App\Http\Controllers\Auth\AuthController@checkActivation',
    ]);
    $api->post('/auth/activate', [
        'as' => 'api.auth.activate',
        'uses' => 'App\Http\Controllers\Auth\AuthController@activate',
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
    $api->post('/content/likeComment', [
        'as' => 'api.content.likeComment',
        'uses' => 'App\Http\Controllers\ContentController@likeComment',
    ]);
    $api->get('/content/getCommentLikes', [
        'as' => 'api.content.getCommentLikes',
        'uses' => 'App\Http\Controllers\ContentController@getCommentLikes',
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
    $api->post('/content/pinPost', [
        'as' => 'api.content.pinPost',
        'uses' => 'App\Http\Controllers\ContentController@pinPost',
    ]);
    $api->post('/content/togglePostStatus', [
        'as' => 'api.content.togglePostStatus',
        'uses' => 'App\Http\Controllers\ContentController@togglePostStatus',
    ]);
    $api->post('/content/clearComments', [
        'as' => 'api.content.clearComments',
        'uses' => 'App\Http\Controllers\ContentController@clearComments',
    ]);
    $api->post('/content/clearLikes', [
        'as' => 'api.content.clearLikes',
        'uses' => 'App\Http\Controllers\ContentController@clearLikes',
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

    // Group
    $api->get('/group/getGroupInformation', [
        'as' => 'api.group.getGroupInformation',
        'uses' => 'App\Http\Controllers\GroupController@getGroupInformation',
    ]);
    $api->get('/group/getGroups', [
        'as' => 'api.group.getGroups',
        'uses' => 'App\Http\Controllers\GroupController@getGroups',
    ]);
    $api->get('/group/getTags', [
        'as' => 'api.group.getTags',
        'uses' => 'App\Http\Controllers\GroupController@getTags',
    ]);
    $api->post('/group/joinGroup', [
        'as' => 'api.group.joinGroup',
        'uses' => 'App\Http\Controllers\GroupController@joinGroup',
    ]);
    $api->get('/group/getGroupMemberships', [
        'as' => 'api.group.getGroupMemberships',
        'uses' => 'App\Http\Controllers\GroupController@getGroupMemberships',
    ]);
    $api->post('/group/createGroup', [
        'as' => 'api.group.createGroup',
        'uses' => 'App\Http\Controllers\GroupController@createGroup',
    ]);
    $api->post('/group/editGroup', [
        'as' => 'api.group.editGroup',
        'uses' => 'App\Http\Controllers\GroupController@editGroup',
    ]);
    $api->get('/group/getRequest', [
        'as' => 'api.group.getRequest',
        'uses' => 'App\Http\Controllers\GroupController@getRequest',
    ]);
    $api->post('/group/updateRequestStatus', [
        'as' => 'api.group.updateRequestStatus',
        'uses' => 'App\Http\Controllers\GroupController@updateRequestStatus',
    ]);
    $api->get('/group/getAllRequests', [
        'as' => 'api.group.getAllRequests',
        'uses' => 'App\Http\Controllers\GroupController@getAllRequests',
    ]);
    $api->get('/group/getAllMembers', [
        'as' => 'api.group.getAllMembers',
        'uses' => 'App\Http\Controllers\GroupController@getAllMembers',
    ]);


    // Search
    $api->get('/search', [
        'as' => 'api.search.searchQuery',
        'uses' => 'App\Http\Controllers\SearchController@searchQuery',
    ]);

    // Settings
    $api->get('/settings', [
        'as' => 'api.settings.getSettings',
        'uses' => 'App\Http\Controllers\SettingsController@getSettings',
    ]);
    $api->post('/settings', [
        'as' => 'api.settings.saveSettings',
        'uses' => 'App\Http\Controllers\SettingsController@saveSettings',
    ]);
    $api->post('/settings/uploadLogo', [
        'as' => 'api.settings.uploadLogo',
        'uses' => 'App\Http\Controllers\SettingsController@uploadLogo',
    ]);
    $api->get('/settings/users', [
        'as' => 'api.settings.getUsers',
        'uses' => 'App\Http\Controllers\SettingsController@getUsers',
    ]);
    $api->post('/settings/user', [
        'as' => 'api.settings.updateUser',
        'uses' => 'App\Http\Controllers\SettingsController@updateUser',
    ]);

    // KnowledgeBase
    $api->get('/knowledgebase/getFolders', [
        'as' => 'api.knowledgebase.getFolders',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@getFolders',
    ]);
    $api->get('/knowledgebase/getFiles', [
        'as' => 'api.knowledgebase.getFiles',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@getFiles',
    ]);
    $api->get('/knowledgebase/getFile', [
        'as' => 'api.knowledgebase.getFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@getFile',
    ]);
    $api->get('/knowledgebase/getFolder', [
        'as' => 'api.knowledgebase.getFolder',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@getFolder',
    ]);
    $api->get('/knowledgebase/createDownloadToken', [
        'as' => 'api.knowledgebase.createDownloadToken',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@createDownloadToken',
    ]);
    $api->get('/knowledgebase/readFile', [
        'as' => 'api.knowledgebase.readFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@readFile',
    ]);
    $api->post('/knowledgebase/createFolder', [
        'as' => 'api.knowledgebase.createFolder',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@createFolder',
    ]);
    $api->post('/knowledgebase/modifyFolder', [
        'as' => 'api.knowledgebase.modifyFolder',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@modifyFolder',
    ]);
    $api->post('/knowledgebase/modifyFile', [
        'as' => 'api.knowledgebase.modifyFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@modifyFile',
    ]);
    $api->post('/knowledgebase/saveFile', [
        'as' => 'api.knowledgebase.saveFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@saveFile',
    ]);
    $api->get('/knowledgebase/getFileHistory', [
        'as' => 'api.knowledgebase.getFileHistory',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@getFileHistory',
    ]);
    $api->post('/knowledgebase/restoreFromHistory', [
        'as' => 'api.knowledgebase.restoreFromHistory',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@restoreFromHistory',
    ]);
    $api->post('/knowledgebase/uploadFile', [
        'as' => 'api.knowledgebase.uploadFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@uploadFile',
    ]);
    $api->post('/knowledgebase/deleteFile', [
        'as' => 'api.knowledgebase.deleteFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@deleteFile',
    ]);
    $api->post('/knowledgebase/deleteFolder', [
        'as' => 'api.knowledgebase.deleteFolder',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@deleteFolder',
    ]);
    $api->post('/knowledgebase/createNewFile', [
        'as' => 'api.knowledgebase.createNewFile',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@createNewFile',
    ]);
    $api->get('/knowledgebase/getPermissions', [
        'as' => 'api.knowledgebase.getPermissions',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@getPermissions',
    ]);
    $api->post('/knowledgebase/modifyPermission', [
        'as' => 'api.knowledgebase.modifyPermission',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@modifyPermission',
    ]);
    $api->post('/knowledgebase/removePermission', [
        'as' => 'api.knowledgebase.removePermission',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@removePermission',
    ]);
    $api->post('/knowledgebase/createPermission', [
        'as' => 'api.knowledgebase.createPermission',
        'uses' => 'App\Http\Controllers\KnowledgeBaseController@createPermission',
    ]);

    // Jobs
    $api->get('/jobs/onlineStatus', [
        'as' => 'api.jobs.onlineStatus',
        'uses' => 'App\Http\Controllers\JobsController@onlineStatus',
    ]);

    // Notifications
    $api->get('/notifications/getNotifications', [
        'as' => 'api.notifications.getNotifications',
        'uses' => 'App\Http\Controllers\NotificationController@getNotifications',
    ]);
    $api->get('/notifications/getInAppNotifications', [
        'as' => 'api.notifications.getInAppNotifications',
        'uses' => 'App\Http\Controllers\NotificationController@getInAppNotifications',
    ]);
    $api->get('/notifications/checkUnreadNotifications', [
        'as' => 'api.notifications.checkUnreadNotifications',
        'uses' => 'App\Http\Controllers\NotificationController@checkUnreadNotifications',
    ]);

    // API
    $api->group([
        'namespace' => 'App\Http\Controllers',
    ], function ($api) {
        $api->get('/', 'APIController@getIndex');
        $api->get('/check', 'APIController@checkConnection');
    });
});
