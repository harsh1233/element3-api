<?php

/* Routes for Book2ski api */

Route::namespace('API')->group(function () {
    
    /* Get auth token */
    Route::post('auth/token','Book2SkiController@getToken');

    /**User APIs list */
    Route::post('customerLogin', 'Book2SkiController@customerLogin');

    Route::post('customerRegistration', 'Book2SkiCrmController@customerRegistration');

    Route::middleware('auth:api')->group(function () {
        /* Get course category list */
        Route::get('courseCategories','Book2SkiController@getCourseCategory');

        /* Get course price list */
        Route::get('pricelist','Book2SkiController@getPriceList');

        /* check customer email exist or not */
        Route::post('checkCustomerEmail','Book2SkiController@checkCustomerEmail');

        /* Create new customer with details */
        Route::post('createCustomerWithDetails','Book2SkiController@createCustomerWithDetails');

        /* Create new booking */
        Route::post('createBooking','Book2SkiController@createBooking');

        /* View booking detail */
        Route::get('viewBooking/{booking_number}','Book2SkiController@viewBooking');

        /**Get meeting point list */
        Route::post('meetingPointList', 'Book2SkiController@meetingPointList');

        /**Get booking sources list */
        Route::get('bookingSourcesList', 'Book2SkiController@bookingSourcesList');

        /**Get Lead list */
        Route::get('leadsList', 'Book2SkiController@leadsList');

         /**Get Difficulty Level list */
         Route::get('difficultyLevelList', 'Book2SkiController@DifficultyLevelList');

        /**Get Lead Contact list */
        Route::get('leadsContactList', 'Book2SkiController@leadsContactList');

        /**Get Lead Contact list */
        Route::post('createMultipleBooking', 'Book2SkiController@createMultipleBooking');

        /**Customer APIs */
        /**Get invoice list */
        Route::post('getInvoiceList', 'Book2SkiController@getInvoiceList');

        /**Get booking list */
        Route::post('getBookings', 'Book2SkiController@getBookings');

        /**Update customer details */
        Route::post('updateCustomerDetails', 'Book2SkiController@updateCustomerDetails');

        /**Update relative customer details */
        Route::post('updateRelativeCustomerDetails/{email}', 'Book2SkiController@updateRelativeCustomerDetails');

        /**Delete relative customer details */
        Route::get('deleteRelativeCustomerDetails/{email}', 'Book2SkiController@deleteRelativeCustomerDetails');

        /**Get master season and daytime list */
        Route::get('seasonDayTimeMasterList', 'Book2SkiController@seasonDayTimeMasterList');

         /** VAT Percentage */
         Route::get('getVat', 'Book2SkiController@getVat');
        
        /*Book2ski crm APIs routes */
        Route::prefix('book2skicrm')->group(function () {
            Route::post('createCustomer','Book2SkiController@createCustomer');
            Route::post('forgetPassword', 'Book2SkiCrmController@forgetPassword');
            Route::post('getCustomersList', 'Book2SkiCrmController@getCustomersList');
            Route::post('getCustomer', 'Book2SkiCrmController@getCustomer');
        });
    });
});