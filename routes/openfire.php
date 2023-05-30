<?php

/* Routes for Openfire chat */

Route::namespace('API')->group(function () {
    Route::middleware('auth:api')->prefix('openfire')->group(function () {

        /* Create new chat by element3 admin */
        Route::post('createE3Chat','OpenfireController@createE3Chat')->middleware('role:A|SA|BM');
        
        /* Get Element3 chat for particular user */
        Route::post('getE3Chat','OpenfireController@getE3Chat');
        
        /* Get user chatroom list */
        Route::post('getUserChatRoom','OpenfireController@getUserChatRoom');
        
        /* Create new chat by element3 admin in chat room */
        Route::post('createRoomChat','OpenfireController@createRoomChat')->middleware('role:A|SA|BM');
        
        /* Get chat detail by room name */
        Route::post('getRoomChat','OpenfireController@getRoomChat');
    });
    /* send message to offline user */
    Route::get('openfire/offlineChat','OpenfireController@offlineChat');
});