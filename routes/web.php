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

//Route::any('authduologin', 'HomeController@authduologin');
//Route::any('duologin', 'HomeController@postDuologin');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/customer_QR_code/{QR_nine_digit_number}', 'API\ContactController@generate_qr');

Route::get('/bookingProcessQr/{QR_nine_digit_number}', 'API\BookingProcessController@getDetailsFromQr');
//->middleware('auth:api');

Route::get('/customer_booking_confirm/{id}', 'API\BookingProcessController@customer_booking_confirm1');

Route::get('/view-invoice-pdf', 'API\BookingProcessController@viewPdfEmailTest');

Route::get('/email-verify/{token}', 'API\UserController@emailVerify');

//This route for confirm the course for instructor he get this link in email in two hour ago and at five pm course alert 
Route::get('/d/instructor/course-confirm','API\Masters\InstructorLevelController@courseConfirm');
Route::get('/testUpdateBooking','API\BookingProcessController@testForBookingUpdateEmail');

/**For instructor app review url */
Route::get('/d/instructor/review-customer', function () {
    return view('course_review_error');
});
Route::get('/d/instructor/course-review', function () {
    return view('course_review_error');
});
/**For customer app review url */
Route::get('/d/customer/course-review', function () {
    return view('course_review_error');
});
/**For customer booking confirm url */
Route::get('/d/customer/confirmation-booking', function () {
    return view('booking_confirmation_error');
});

Route::any('duologin', 'API\UserController@duologin');

/* Route::get('email_test', function () {
    return view('email.customer.booking_alert');
}); */


/* Concardis  */
Route::get('/concardis-checkout-page', 'API\ConcardisController@checkout')->name('concardis.checkout');
Route::get('/concardis-accept-url', 'API\ConcardisController@accept')->name('concardis.accept');
Route::get('/concardis-decline-url', 'API\ConcardisController@decline')->name('concardis.decline');
Route::get('/concardis-exception-url', 'API\ConcardisController@exception')->name('concardis.exception');
Route::get('/concardis-cancel-url', 'API\ConcardisController@cancel')->name('concardis.cancel');
