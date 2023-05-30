<?php

/* Concardis Routes */
Route::namespace('API')->group(function () {
    Route::prefix('concardis')->group(function () {
        Route::get('generate-invoice-uuid', 'ConcardisController@generateInvoiceUUID');
        Route::get('booking-details/{uuid}', 'ConcardisController@getBookingDetails');
        Route::post('sign', 'ConcardisController@sign');
    });
});
