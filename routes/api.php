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

Route::namespace('API')->middleware('localization')->group(function () {
    Route::prefix('user')->group(function () {
        Route::post('login', 'UserController@login');
        Route::post('forgetPassword', 'UserController@forgetPassword');
        Route::post('resetPassword', 'UserController@resetPassword');
        Route::post('changePassword', 'UserController@changePassword')->middleware('auth:api');
        Route::post('updateProfile', 'UserController@updateProfile')->middleware('auth:api');
        Route::post('userNotificationList', 'UserController@userNotificationList')->middleware('auth:api');
        Route::post('logout', 'UserController@logout')->middleware('auth:api');
        Route::post('appRegistration', 'UserController@app_registration');
        Route::post('appLogin', 'UserController@app_login');
        Route::post('updateDeviceToken', 'UserController@updateDeviceToken')->middleware('auth:api');
        Route::post('instructorRegistration', 'UserController@instructorRegistration');
        /**crm action trails list */
        Route::post('getCrmTrails', 'UserController@getCrmTrails');
        Route::get('getCrmUsersList', 'UserController@getCrmUsersList');
    });

    Route::middleware('auth:api')->group(function () {
        Route::namespace('Masters')->prefix('masters')->group(function () {
            /** Subcategory routes */
            Route::get('categories', 'SubcategoryController@getCategories');
            Route::get('subcategories', 'SubcategoryController@getSubcategories');
            Route::post('subcategory/create', 'SubcategoryController@createSubcategory');
            Route::post('subcategory/update/{id}', 'SubcategoryController@updateSubcategory');
            Route::get('subcategory/delete/{id}', 'SubcategoryController@deleteSubcategory');

            /** Language routes */
            Route::get('languages', 'LanguageController@getLanguages');
            Route::post('language/create', 'LanguageController@createLanguage');
            Route::post('language/update/{id}', 'LanguageController@updateLanguage');
            Route::get('language/delete/{id}', 'LanguageController@deleteLanguage');
            Route::post('languageswithpagination', 'LanguageController@getLanguagesWithPagination');

            /** Country routes */
            Route::get('countries', 'CountryController@getCountries');
            Route::post('country/create', 'CountryController@createCountry');
            Route::post('country/update/{id}', 'CountryController@updateCountry');
            Route::get('country/delete/{id}', 'CountryController@deleteCountry');

            /** Instructor level routes */
            Route::get('instructorLevels', 'InstructorLevelController@getLevels');
            Route::post('instructorLevel/create', 'InstructorLevelController@createLevel');
            Route::post('instructorLevel/update/{id}', 'InstructorLevelController@updateLevel');
            Route::get('instructorLevel/delete/{id}', 'InstructorLevelController@deleteLevel');
            Route::post('getAvailableInstructor', 'InstructorLevelController@getAvailableInstructor');
            Route::post('getInstuctorCourse', 'InstructorLevelController@getInstuctorCourse');
            Route::post('getCustomerCourse', 'InstructorLevelController@getCustomerCourse');
            Route::get('instuctorOngoingCourseParticipate', 'InstructorLevelController@getInstructorOngoingCourseParticipateListing');
            Route::post('getInstructorLeaves', 'InstructorLevelController@getInstructorLeaves');
            Route::post('getAvailableInstructorNew', 'InstructorLevelController@getAvailableInstructorNew');
            Route::post('changeCourseConfirmStatus', 'InstructorLevelController@changeCourseConfirmStatus');
            Route::post('quickCheckInstructor', 'InstructorLevelController@quickCheckInstructor');
            Route::post('checkInstructorLanguageConflict', 'InstructorLevelController@checkInstructorLanguageConflict');


            /** Allergies routes */
            Route::get('allergies', 'AllergyController@getAllergies');
            Route::post('allergy/create', 'AllergyController@createAllergy');
            Route::post('allergy/update/{id}', 'AllergyController@updateAllergy');
            Route::get('allergy/delete/{id}', 'AllergyController@deleteAllergy');

            /** Office / Branch routes */
            Route::get('offices', 'OfficeController@getOffices');
            Route::post('office/create', 'OfficeController@createOffice');
            Route::post('office/update/{id}', 'OfficeController@updateOffice');
            Route::get('office/delete/{id}', 'OfficeController@deleteOffice');
            Route::get('office/view/{id}', 'OfficeController@view');

            /** Salary Group routes */
            Route::get('salaryGroups', 'SalaryGroupController@getSalaryGroups');
            Route::post('salaryGroup/create', 'SalaryGroupController@createSalaryGroup');
            Route::post('salaryGroup/update/{id}', 'SalaryGroupController@updateSalaryGroup');
            Route::get('salaryGroup/delete/{id}', 'SalaryGroupController@deleteSalaryGroup');
            Route::get('salaryGroup/view/{id}', 'SalaryGroupController@viewSalaryGroup');
            Route::get('getSalaryGroupWiseContacts/{id}', 'SalaryGroupController@getSalaryGroupWiseContacts'); /* Contact List Salary Group Wise */
            Route::post('changeInstructorSalaryGroup', 'SalaryGroupController@changeInstructorSalaryGroup'); /* Move Contact to other Salary Group */

            /** Static Content update */
            Route::post('staticContent/update/{id}', 'StaticContentController@updateStaticContent');
            Route::get('getStaticContent', 'StaticContentController@getStaticContent');

            /** Payment method update */
            Route::post('paymentMethod/update/{id}', 'PaymentMethodController@updatePaymentMethod');
            Route::get('getPaymentMethod', 'PaymentMethodController@getPaymentMethod');
            Route::get('getCreditCardTypes', 'PaymentMethodController@getCreditCardTypes');

            /** Leave Master */
            Route::get('getLeavesTypes', 'LeaveController@getLeaves');

            /** VAT Percentage */
            Route::get('getVat', 'OfficeController@getVat');
            Route::post('setVat', 'OfficeController@setVat');

            /** Meeting Point routes */
            Route::post('meetingPoint/create', 'MeetingPointController@create');
            Route::post('meetingPoint/update/{id}', 'MeetingPointController@update');
            Route::get('meetingPoint/delete/{id}', 'MeetingPointController@delete');
            Route::post('meetingPoint/list', 'MeetingPointController@list');
            Route::post('meetingPoint/changeStatus/{id}', 'MeetingPointController@changeStatus');
            Route::get('meetingPoint/view/{id}', 'MeetingPointController@view');

            /** Season routes */
            Route::post('seasonDaytime/update', 'SeasonDaytimeController@updateSeason');
            Route::get('seasonDaytime/view/{id}', 'SeasonDaytimeController@view');
            Route::get('seasonDaytime/list', 'SeasonDaytimeController@list');

            /* Instructor block label routes */
            Route::prefix('BlockLabel')->group(function () {
                Route::post('create', 'BlockLabelController@create');
                Route::post('list', 'BlockLabelController@list');
                Route::post('update/{id}', 'BlockLabelController@update');
                Route::get('view/{id}', 'BlockLabelController@view');
                Route::delete('delete/{id}', 'BlockLabelController@delete');
            });
        });

        /* Contact Routes */
        Route::prefix('contact')->group(function () {
            Route::post('create', 'ContactController@createContact');
            Route::post('update/{id}', 'ContactController@updateContact');
            Route::get('delete/{id}', 'ContactController@deleteContact');
            Route::get('view/{id}', 'ContactController@viewContact');
            Route::post('changeStatus', 'ContactController@changeStatus');
            Route::post('list', 'ContactController@contactList');
            Route::get('ActiveContactList', 'ContactController@ActiveContactList');
            Route::post('getInstructorsCalender', 'ContactController@getInstructorsCalender');
            Route::post('editPreferPaymentMethod', 'ContactController@editPreferPaymentMethod');
            Route::post('assignCustomerTeachingMaterial', 'ContactController@assignCustomerTeachingMaterial');
            Route::post('getCustomerAndInstructorUpdates', 'ContactController@getCustomerAndInstructorUpdates');
            Route::post('getInstructorNotMappedSalaryGroup', 'ContactController@getInstructorNotMappedSalaryGroup');
            Route::post('sendInvitationCode', 'ContactController@sendInvitationCode');
            Route::post('contactBookings', 'ContactController@contactBookings');
            Route::post('getRelevantCustomersList', 'ContactController@getRelevantCustomersList');
            Route::post('sendcustomerArrivalNotifyInstructor', 'ContactController@customerArrivalNotifyInstructor');
            Route::post('instructorArrivalNotifyCustomer', 'ContactController@instructorArrivalNotifyCustomer');

            /**This API for use from add booking time add customer */
            Route::post('addCustomer', 'ContactController@addCustomer');
            Route::post('importContactData', 'ContactController@importContactData');

            Route::post('checkCustomerExist', 'ContactController@checkCustomerExist');
        });

        /**Contact sub child routes */
        Route::prefix('subChild')->group(function () {
            Route::post('addSubChild', 'SubChildContactController@addSubChild');
            Route::post('subChildList', 'SubChildContactController@subChildList');
            Route::post('updateSubChild/{id}', 'SubChildContactController@updateSubChild');
        });

        /* Customer group routes */
        Route::prefix('customerGroup')->group(function () {
            Route::post('create', 'CustomerGroupController@createGroup');
            Route::post('update/{id}', 'CustomerGroupController@updateGroup');
            Route::post('list', 'CustomerGroupController@listGroup');
            Route::get('delete/{id}', 'CustomerGroupController@deleteGroup');
            Route::get('getCustomers', 'CustomerGroupController@getCustomers');
            Route::get('getGroup/{id}', 'CustomerGroupController@getGroup');
        });

        /* Contact Leave routes */
        Route::prefix('contactLeave')->group(function () {
            Route::post('create', 'ContactLeaveController@createContactLeave');
            Route::post('update/{id}', 'ContactLeaveController@updateContactLeave');
            Route::get('list', 'ContactLeaveController@getContactLeavesList');
            Route::get('delete/{id}', 'ContactLeaveController@deleteContactLeave');
            Route::get('view/{id}', 'ContactLeaveController@viewContactLeave');
            Route::get('getLeavesTypes', 'ContactLeaveController@getLeaves');
            Route::post('checkvalidationforBooking', 'ContactLeaveController@checkInstructorBookingValidation');
            Route::post('updatePaidStatus/{id}', 'ContactLeaveController@updatePaidStatus');
            Route::get('cancelContactLeave/{id}', 'ContactLeaveController@cancelContactLeave');
        });

        /* Menu and privilege routes */
        Route::prefix('permissions')->group(function () {
            Route::get('getAllMenus', 'PermissionController@getAllMenus');
            Route::get('getUserMenus/{role_id}', 'PermissionController@getUserMenus');

            Route::post('createPrivilege', 'PermissionController@createPrivilege');
            Route::post('updatePrivilege/{id}', 'PermissionController@updatePrivilege');
            Route::get('deletePrivilege/{id}', 'PermissionController@deletePrivilege');
            Route::get('getAllPrivileges', 'PermissionController@getAllPrivileges');
            Route::get('viewPrivilege/{id}', 'PermissionController@viewPrivilege');

            Route::post('createRole', 'PermissionController@createRole');
            Route::post('updateRole/{id}', 'PermissionController@updateRole');
            Route::get('deleteRole/{id}', 'PermissionController@deleteRole');
            Route::get('getAllRoles', 'PermissionController@getAllRoles');
            Route::get('viewRole/{id}', 'PermissionController@viewRole');

            /**Check role base url accessable or not */
            Route::post('checkRoleBaseUrl', 'PermissionController@checkRoleBaseUrl');
        });

        /* User management routes */
        Route::prefix('userManagement')->group(function () {
            Route::get('getEmployees', 'UserController@getEmployees');
            Route::get('getUsers', 'UserController@getUsers');
            Route::post('addUser', 'UserController@addUser');
            Route::post('updateUserRole', 'UserController@updateUserRole');
            Route::post('changeStatus', 'UserController@changeStatus');
            Route::post('changeNotificationStatus', 'UserController@changeNotificationStatus');
            Route::get('getUserProfileStatus/{id}', 'UserController@getUserProfileStatus');
            Route::get('deleteUser/{id}', 'UserController@deleteUser');
            Route::post('sendPassword', 'UserController@sendPassword');
        });

        /* Course routes */
        Route::prefix('course')->namespace('Courses')->group(function () {
            //Course category
            Route::post('getCategories', 'CourseCategoryController@listCategory');
            Route::get('viewCategory/{id}', 'CourseCategoryController@viewCategory');
            Route::post('createCategory', 'CourseCategoryController@createCategory');
            Route::post('updateCategory/{id}', 'CourseCategoryController@updateCategory');
            Route::post('changeCategoryStatus/{id}', 'CourseCategoryController@changeStatus');

            //Course detail
            Route::get('getCourses', 'CourseController@listCourse');
            Route::post('getCoursesPagination', 'CourseController@listCoursePagination');
            Route::get('viewCourse/{id}', 'CourseController@viewCourse');
            Route::post('createCourse', 'CourseController@createCourse');
            Route::post('updateCourse/{id}', 'CourseController@updateCourse');
            Route::post('changeCourseStatus/{id}', 'CourseController@changeStatus');
            Route::post('listCustomerCourse', 'CourseController@listCustomerCourse');
            Route::post('listCustomerCourseNew', 'CourseController@listCustomerCourseNew');
            Route::get('getCourseFromQr/{QR_nine_digit_number}', 'CourseController@getCourseFromQr');
            Route::get('getCourseDetailIdAndPrice', 'CourseController@getCourseDetailIdAndPrice');
            Route::post('assignTeachingMaterialCorse', 'CourseController@assignTeachingMaterialCorse');

            Route::get('getDifficultyLevel', 'CourseController@getDifficultyLevel');
            Route::post('updateDisplayWebsiteStatus', 'CourseController@updateDisplayWebsiteStatus');
            Route::post('updateArchivedStatus', 'CourseController@updateArchivedStatus');
            Route::delete('deleteCourse/{id}', 'CourseController@deleteCourse');
        });

        /* Teaching material routes */
        Route::prefix('teachingCategory')->group(function () {
            //Teaching material category
            Route::post('getTeachingCategories', 'TeachingMaterialCategoryController@listTeachingCategory');
            Route::post('createTeachingCategory', 'TeachingMaterialCategoryController@createTeachingCategory');
            Route::post('updateTeachingCategory/{id}', 'TeachingMaterialCategoryController@updateTeachingCategory');
            Route::get('deleteTeachingCategory/{id}', 'TeachingMaterialCategoryController@deleteTeachingCategory');
            Route::post('changeTeachingMaterialCategoryStatus/{id}', 'TeachingMaterialCategoryController@changeStatus');

            //Teaching material
            Route::post('getTeachingMaterial', 'TeachingMaterialController@listTeachingMaterial');
            Route::post('getTeachingMaterialWithParentZero', 'TeachingMaterialController@listTeachingMaterialWithParentZero');
            Route::post('createTeachingMaterial', 'TeachingMaterialController@createTeachingMaterial');
            Route::post('updateTeachingMaterial/{id}', 'TeachingMaterialController@updateTeachingMaterial');
            Route::get('deleteTeachingMaterial/{id}', 'TeachingMaterialController@deleteTeachingMaterial');
            Route::post('changeTeachingMaterialStatus/{id}', 'TeachingMaterialController@changeStatus');
            Route::post('viewTeachingMateria/{id}', 'TeachingMaterialController@viewTeachingMaterial');
        });

        /* Booking Process Routes */
        Route::prefix('BookingProcess')->group(function () {
            Route::post('createBookingProcess', 'BookingProcessController@createBookingProcess');
            Route::post('updateBookingProcess/{id}', 'BookingProcessController@updateBookingProcess');
            Route::get('deleteBookingProcess/{id}', 'BookingProcessController@deleteBookingProcess');
            Route::get('getSourceList', 'BookingProcessController@bookingProcessSourceList');
            Route::post('bookingProcessList', 'BookingProcessController@bookingProcessList');
            Route::post('bookingProcessCalenderList', 'BookingProcessController@bookingProcessCalenderList');
            Route::post('bookingProcessCalenderListInstructorWise', 'BookingProcessController@bookingProcessCalenderListInstructorWise');
            Route::post('assignInstructorBooking', 'BookingProcessController@assignInstructorBooking');
            Route::post('bookingProcessListNew', 'BookingProcessController@bookingProcessListNew');
            Route::post('viewBookingProcess/{id}', 'BookingProcessController@viewBookingProcess');
            Route::post('updateContact', 'BookingProcessController@updateContact');
            Route::post('updateContactDetail/{id}', 'BookingProcessController@updateContactDetail');
            Route::post('getContactList', 'BookingProcessController@getContactList');
            Route::post('enrolledBookingProcessCourse', 'BookingProcessController@enrolledBookingProcessCourse');
            Route::post('getCourseDetailsWithBookingId', 'BookingProcessController@getCourseDetailsWithBookingId');
            Route::post('getInstructorDetailsWithBookingId', 'BookingProcessController@getInstructorDetailsWithBookingId');
            Route::get('getOngoingCourseDetailWithBookingDetail', 'BookingProcessController@getOngoingCourseDetailWithBookingDetail');
            Route::post('changeBookingProcessTrashStatus', 'BookingProcessController@changeBookingProcessTrashStatus');
            Route::get('changeBookingProcessDraftStatus/{id}', 'BookingProcessController@changeBookingProcessDraftStatus');
            Route::post('changeBookingProcessDraftStatusBulk', 'BookingProcessController@changeBookingProcessDraftStatusBulk');
            Route::post('cloneBookingProcess', 'BookingProcessController@cloneBookingProcess');
            Route::post('getParticipateListingBookingIdBase', 'BookingProcessController@getParticipateListingBookingIdBase');
            Route::post('getBookingDetails', 'BookingProcessController@getBookingDetails');
            Route::post('bookingProcessListCourseBase', 'BookingProcessController@bookingProcessListCourseBase');
            Route::post('customerInvoiceList', 'BookingProcessController@customerInvoiceList');
            Route::get('getInvoice/{id}', 'BookingProcessController@getInvoice');
            Route::post('changeTaxConsultantStatus', 'BookingProcessController@changeTaxConsultantStatus');
            Route::post('changeTaxConsultantStatusMultiple', 'BookingProcessController@changeTaxConsultantStatusMultiple');
            Route::post('againSendInvoiceCustomer', 'BookingProcessController@againSendInvoiceCustomer');
            Route::post('getOngoingCourseDetail', 'BookingProcessController@getOngoingCourseDetail');
            Route::post('getRuningCourseDetail', 'BookingProcessController@getRuningCourseDetail');
            Route::post('againSendCourseAlertInstructor', 'BookingProcessController@againSendCourseAlertInstructor');
            Route::post('sendBookingDetailtoInstructor', 'BookingProcessController@sendBookingDetailtoInstructor');

            //after this api is for test invoice
            Route::get('makeBookingProcessInvoice', 'BookingProcessController@makeBookingProcessInvoice');

            // transfer booking for customer by instructor
            Route::post('transferBooking', 'BookingProcessController@transferBooking');

            // transfer customer in booking for customer by admin
            //{id} = Course detail id for exsiting customer booking
            Route::get('getBookingsCourseDetailBase/{id}', 'BookingProcessController@getBookingsCourseDetailBase');
            Route::post('transferBookingCustomer', 'BookingProcessController@transferBookingCustomer');

            //change instructor
            Route::post('changeInstructor', 'BookingProcessController@changeInstructor');

            Route::get('callObonoApi', 'BookingProcessController@callObonoApi');

            /**Get same booking list */
            Route::post('getCommonBookingList', 'BookingProcessController@getCommonBookingList');
            /**Merge booking */
            Route::post('mergeBooking', 'BookingProcessController@mergeBooking');

            /**Attend booking */
            Route::get('attendBooking', 'BookingProcessController@attendBooking');

            /**Cancel booking */
            Route::post('cancelBooking', 'BookingProcessController@cancelBooking');

            /**Booking attend from qr */
            Route::post('assignBookingFromQr', 'BookingProcessController2@assignBookingFromQr');
            /**Get invoice history list */
            Route::post('invoiceHistoryList', 'BookingProcessController@invoiceHistoryList');

            Route::post('twoWeekPendingInvoiceList', 'BookingProcessController2@twoWeekPendingInvoiceList');
            Route::post('sendTwoWeekPendingInvoiceReminder', 'BookingProcessController2@sendTwoWeekPendingInvoiceReminder');
        });

        /* Leave management Routes */
        Route::prefix('BookingEstimate')->group(function () {
            Route::post('createBookingEstimate', 'BookingEstimateController@createBookingEstimate');
            Route::post('updateBookingEstimate/{id}', 'BookingEstimateController@updateBookingEstimate');
            Route::get('deleteBookingEstimate/{id}', 'BookingEstimateController@deleteBookingEstimate');
            Route::post('bookingEstimateList', 'BookingEstimateController@bookingEstimateList');
            Route::post('sendBookingEstimateEmail/{id}', 'BookingEstimateController@sendBookingEstimateEmail');
            Route::get('getBookingEstimateDetails/{id}', 'BookingEstimateController@getBookingEstimateDetails');

            Route::post('createMultipleBookingEstimate', 'BookingEstimateController@createMultipleBookingEstimate');
            Route::post('sendMultipleBookingEstimateEmail', 'BookingEstimateController@sendMultipleBookingEstimateEmail');
            Route::post('multipleEstimateToBooking', 'BookingEstimateController@multipleEstimateToBooking');

            //Route::get('downloadBookingEstimateInvoice/{id}', 'BookingEstimateController@downloadBookingEstimateInvoice');
        });

        /* Leave management Routes */
        Route::prefix('LeaveManagement')->group(function () {
            Route::post('createRequest', 'LeaveManagementController@createLeaveRequest');
            Route::post('listRequest', 'LeaveManagementController@listLeaveRequest');
            Route::post('changeRequestStatus', 'LeaveManagementController@changeRequestStatus');
            Route::post('RequestListDashboard', 'LeaveManagementController@LeaveRequestListDashboard');
        });

        /* Expenditure Routes */
        Route::prefix('expenditure')->group(function () {
            Route::post('list', 'ExpenditureController@list');
            Route::get('{id}', 'ExpenditureController@get');
            Route::post('create', 'ExpenditureController@create');
            Route::post('update', 'ExpenditureController@update');
            Route::post('delete/{id}', 'ExpenditureController@delete');
            Route::post('updateStatus/{id}', 'ExpenditureController@updateStatus')->middleware('role:A|SA|FM');
            Route::post('updateMultipleStatus', 'ExpenditureController@updateMultipleStatus')->middleware('role:A|SA|FM');
            Route::post('updateMultiplePaymentStatus', 'ExpenditureController@updateMultiplePaymentStatus')->middleware('role:A|SA|FM');
        });

        /* Cash Routes */
        Route::prefix('cash')->group(function () {
            Route::post('list', 'CashController@list');
            Route::get('{id}', 'CashController@get');
            Route::post('create', 'CashController@create');
            Route::post('update', 'CashController@update');
            Route::get('delete/{id}', 'CashController@delete');
            Route::post('report', 'CashController@report');
        });

        /* Voucher Routes */
        Route::prefix('voucher')->group(function () {
            Route::post('list', 'VoucherController@list');
            Route::get('{id}', 'VoucherController@get');
            Route::post('check', 'VoucherController@check');
            Route::post('create', 'VoucherController@create');
            Route::post('update', 'VoucherController@update');
            Route::get('delete/{id}', 'VoucherController@delete');

            /* no one can apply voucher via api */
            // Route::post('apply', 'VoucherController@apply');
        });

        /* Instructor activity routes */
        Route::prefix('InstructorActivity')->group(function () {
            Route::post('create', 'InstructorActivityController@create');
            Route::post('offlineCreate', 'InstructorActivityController@offlineCreate');
            Route::post('getActivity', 'InstructorActivityController@getActivity');
            Route::post('addComment', 'InstructorActivityController@addComment');
            Route::post('getComments', 'InstructorActivityController@getComments');
            Route::post('getTotalTimesheetGraph', 'InstructorActivityController@getTotalTimesheetGraph');
            Route::post('activityConfirmationRequest', 'InstructorActivityController@activityConfirmationRequest');
            Route::post('updateConfirmationRequest', 'InstructorActivityController@updateConfirmationRequest')->middleware('role:A|SA');
            Route::post('activityTimesheetList', 'InstructorActivityController@activityTimesheetList');
            Route::post('bookingList', 'InstructorActivityController@bookingList');
            Route::post('loggedTime', 'InstructorActivityController@loggedTime');
            Route::get('viewActivityTimesheet/{id}', 'InstructorActivityController@viewActivityTimesheet');
            Route::post('updateActivityTimesheet', 'InstructorActivityController@updateActivityTimesheet');
        });

        /* Payment Routes */
        Route::prefix('payment')->group(function () {
            Route::post('list', 'PaymentController@list');
            Route::post('getRecordPaymentData', 'PaymentController@getRecordPaymentData');
            Route::post('recordPayment', 'PaymentController@recordPayment');
            Route::post('getChartPayment', 'PaymentController@getChartPayment');
        });

        /* Mountain Routes */
        Route::prefix('mountain')->group(function () {
            Route::get('getMountain', 'MountainController@getMountainList');
            Route::post('getSkiLift', 'MountainController@getMountainSkiLift');
            Route::post('getSlop', 'MountainController@getMountainSlopes');

            Route::get('getPropertyInXml', 'MountainController@getPropertyInXml');
            Route::post('getLiftInXml', 'MountainController@getLiftInXml');
            Route::post('getSlopeInXml', 'MountainController@getSlopeInXml');
        });

        /* Feedback Routes */
        Route::prefix('feedback')->group(function () {
            Route::get('getQuestions', 'FeedbackController@getQuestions');
            Route::post('createFeedback', 'FeedbackController@createFeedback');
            Route::get('viewFeedback/{id}', 'FeedbackController@viewFeedback');
            Route::post('listFeedback', 'FeedbackController@listFeedback');
            Route::get('deleteFeedback/{id}', 'FeedbackController@deleteFeedback')->middleware('role:A|SA|BM');
        });

        /* Payroll Routes */
        Route::prefix('payroll')->group(function () {
            Route::post('list', 'PayrollController@list');
            Route::get('{id}', 'PayrollController@get');
            Route::post('view/{id}', 'PayrollController@view');
            Route::post('create', 'PayrollController@create');
            Route::get('delete/{id}', 'PayrollController@delete');
            Route::get('refresh/{id}', 'PayrollController@refresh');
            Route::post('checkInstructorTimeSheetActivity', 'PayrollController@checkInstructorActivityTimesheetPayroll'); //Check For Instructor Timesheet Pending / Confirm When generating payroll
            Route::post('InstructorTimeSheetActivityPayroll', 'PayrollController@getPayrollContactActivityTimeSheet'); //Payroll Wise Instructor Activity Timesheet
            Route::get('getContactPlaySlips/{id}', 'PayrollController@getContactPlaySlips'); //Get Payroll Wise Instructor Activity Timesheet

            Route::post('sendNotificationInstructorTimesheetPending', 'PayrollController@sendNotificationInstructorTimesheetPending'); //Check For Instructor Timesheet Pending / Confirm When generating payroll

            /* Payslips Routes */
            Route::prefix('payslip')->group(function () {
                Route::get('{id}', 'PayrollController@getPayslip');
                Route::post('update', 'PayrollController@updatePayslip');
                Route::get('refresh/{id}', 'PayrollController@refreshPayslip');
                Route::post('changeStatus', 'PayrollController@changeStatusPayslip');
                Route::get('email/{id}', 'PayrollController@emailPayslip');

                Route::post('paySlipsWithPagination', 'PayrollController@paySlipsWithPagination');
            });
        });

        /* Todo routes */
        Route::prefix('todo')->group(function () {
            Route::get('getAdmins', 'TodoController@getAdmins');
            Route::post('createTodo', 'TodoController@createTodo');
            Route::post('updateTodo/{id}', 'TodoController@updateTodo');
            Route::get('deleteTodo/{id}', 'TodoController@deleteTodo');
            Route::post('assignToAdmin/{id}', 'TodoController@assignToAdmin');
            Route::get('markAsDone/{id}', 'TodoController@markAsDone');
            Route::post('listTodo', 'TodoController@listTodo');
            Route::post('listTodoAction', 'TodoController@listTodoAction');
            Route::post('deleteTodoAction', 'TodoController@deleteTodoAction');
            Route::post('readTodoAction', 'TodoController@readTodoAction');
        });

        /* Signature routes */
        Route::prefix('signature')->group(function () {
            Route::get('getDocuments', 'SignatureController@getDocuments');
            Route::post('uploadSignature', 'SignatureController@uploadSignature');
            Route::post('uploadContactDocument', 'SignatureController@uploadContactDocument');
            Route::post('getContactDocuments', 'SignatureController@getContactDocuments');
            Route::post('removeContactDocument', 'SignatureController@removeContactDocument');

            Route::middleware('role:A|SA|FM')->group(function () {
                Route::post('getSignatureList', 'SignatureController@getSignatureList');
                Route::post('getSignature', 'SignatureController@getSignature');
                Route::post('getTimesheetSignature', 'SignatureController@getTimesheetSignature');
            });
        });

        /* Participants attendances routes */
        Route::prefix('ParticipantsAttendances')->group(function () {
            Route::post('saveParticipantsAttendances', 'InstructorActivityController@saveParticipantsAttendances');
            Route::post('listParticipantsAttendances', 'InstructorActivityController@listParticipantsAttendances');
            Route::get('getParticipantsAttendances/{id}', 'InstructorActivityController@getParticipantsAttendances');
        });


        /* Participants attendances routes */
        Route::prefix('SeasonTicket')->group(function () {
            Route::post('create', 'SeasonTicketController@create')->middleware('role:A|SA|BM');
            Route::post('update/{id}', 'SeasonTicketController@update')->middleware('role:A|SA|BM');
            Route::get('view/{id}', 'SeasonTicketController@view');
            Route::post('list', 'SeasonTicketController@list');
            Route::delete('delete/{id}', 'SeasonTicketController@delete')->middleware('role:A|SA|BM');
            Route::post('convertSeasonTicketToBooking', 'SeasonTicketController@convertSeasonTicketToBooking');
            Route::post('sendSeasonTicketEmail/{id}', 'SeasonTicketController@sendSeasonTicketEmail');
            Route::post('seasonTicketBookings', 'SeasonTicketController@seasonTicketBookings');
        });

        /* Participants attendances routes */
        Route::prefix('InstructorBlock')->group(function () {
            Route::post('create', 'InstructorBlockController@create');
            Route::post('update/{id}', 'InstructorBlockController@update');
            Route::post('list', 'InstructorBlockController@list');
            Route::get('view/{id}', 'InstructorBlockController@view');
            Route::delete('delete/{id}', 'InstructorBlockController@delete');
            Route::post('checkBlockAvailable', 'InstructorBlockController@checkBlockAvailable');
            Route::post('getBlocks', 'InstructorBlockController@getBlocks');
            Route::post('deleteMultiple', 'InstructorBlockController@deleteMultiple');
            Route::post('updateMultiple', 'InstructorBlockController@updateMultiple');
        });

        /* Participants attendances routes */
        Route::prefix('ConsolidatedInvoice')->group(function () {
            Route::post('create', 'ConsolidatedInvoiceController@create');
            Route::post('list', 'ConsolidatedInvoiceController@list');
            Route::delete('delete/{id}', 'ConsolidatedInvoiceController@delete');
            Route::post('update/{id}', 'ConsolidatedInvoiceController@update');
            Route::post('updatePaymentStatus', 'ConsolidatedInvoiceController@updatePaymentStatus');
            Route::post('sendAgainInvoice', 'ConsolidatedInvoiceController@sendAgainInvoice');
        });

        //Customer booking QR code
        Route::get('/bookingCustomerQr/{QR_number}', 'BookingProcessController@getBookingCustomerDetailFromQr');

        /*Elda routes */
        Route::prefix('elda')->group(function () {
            Route::post('list', 'EldaContoller@list');
            Route::get('getFunctionsNames', 'EldaContoller@getFunctionsNames');
            Route::post('registerEldaDetails', 'EldaContoller@registerEldaDetails');
            Route::post('deRegisterEldaDetails', 'EldaContoller@deRegisterEldaDetails');
            Route::delete('delete/{id}', 'EldaContoller@delete');
            Route::post('ftpProcessList', 'EldaContoller@ftpProcessList');
        });

        /*Chat routes */
        Route::prefix('chat')->group(function () {
            Route::post('sendE3Chat', 'ChatController@sendE3Chat');
            Route::post('e3ChatList', 'ChatController@e3ChatList');
            Route::post('notificationRead', 'ChatController@notificationRead');
            Route::post('notificationList', 'ChatController@notificationList');
            Route::post('chatRead', 'ChatController@chatRead');
        });

        /* Season schedular routes */
        Route::prefix('SeasonSchedular')->group(function () {
            Route::post('create', 'SeasonSchedularController@create');
            Route::post('list', 'SeasonSchedularController@list');
            Route::get('view/{id}', 'SeasonSchedularController@view');
            Route::delete('delete/{id}', 'SeasonSchedularController@delete');
            Route::post('update', 'SeasonSchedularController@update');
            Route::post('getSeasonSchedulars', 'SeasonSchedularController@getSeasonSchedulars');
        });
    });

    /* This all routes are define for export CSV file which applicable in admin panel */
    Route::get('contactExport', 'ContactController@contactExport');
    Route::get('bookingProcessExport', 'BookingProcessController@bookingProcessList'); //set is_export as a parameter
    Route::get('usersExport', 'UserController@getUsers');
    Route::get('customerGroupExport', 'CustomerGroupController@listGroup');
    Route::get('contactLeaveExport', 'ContactLeaveController@getContactLeavesList');
    Route::get('coursesExport', 'Courses\CourseController@listCourse');
    Route::get('teachingMaterialExport', 'TeachingMaterialController@listTeachingMaterial');
    Route::get('bookingEstimateExport', 'BookingEstimateController@bookingEstimateList');
    Route::get('customerInvoiceExport', 'BookingProcessController@customerInvoiceList'); //pass with contact_id
    Route::get('expenditureExport', 'ExpenditureController@list');
    Route::get('paymentExport', 'PaymentController@list'); //pass with contact_id
    Route::get('cashExport', 'CashController@list'); //office_id=6&date=2019-10-14 this filed are required
    Route::get('cashReportExport', 'CashController@report'); //office_id=6&month=07&year=2019 this filed are required
    Route::get('voucherExport', 'VoucherController@list');
    Route::get('payrollExport/{id}', 'PayrollController@view'); //id = payroll id must be passed
    Route::get('payrollListExport', 'PayrollController@list'); //payroll list export
    Route::get('languagesListExport', 'Masters\LanguageController@getLanguagesWithPagination'); //Languages list export
    Route::get('meetingPointExport', 'Masters\MeetingPointController@list'); //Meeting Points list export
    Route::get('instructorBlockExport', 'InstructorBlockController@list'); //Instructor block list export
    Route::get('seasonTicketExport', 'SeasonTicketController@list'); //Season ticket list export
    Route::get('consolidatedInvoiceExport', 'ConsolidatedInvoiceController@list'); //Consolidated invoice list export
    Route::get('eldaDetailsExport', 'EldaContoller@list'); //Elda details xls list export
    Route::get('eldaDetailsTxtExport', 'EldaContoller@txtExport'); //Elda details txt list export
    Route::get('exportCourseBaseBookings', 'Courses\CourseController@exportCourseBaseBookings');
    Route::get('eldaRegistration/{id}', 'EldaContoller@EldaRegistration'); // Generate GKK file for Registration proceess in elda
    Route::get('eldaRequestInsuranceNo/{id}', 'EldaContoller@EldaRequestAnInsuranceNo'); //Generate GKK file for Request An Insurance No in elda
    Route::get('eldaDeRegistration/{id}', 'EldaContoller@EldaDeregistration'); //Generate GKK file for DeRegistration proceess in elda
    Route::get('eldaCancelRegistration/{id}', 'EldaContoller@EldaCancelRegistration'); //Generate GKK file for Cancel Registration proceess in elda
    Route::get('eldaCancelDeRegistration/{id}', 'EldaContoller@EldaCancellation'); //Generate GKK file for Cancellation proceess in elda


    /* end  */


    /* Payslips Routes */
    Route::get('payslip/download/{id}', 'PayrollController@downloadPayslip');
    Route::get('viewPdf', 'BookingProcessController@viewPdf');
    Route::get('downloadBookingEstimateInvoice/{id}', 'BookingEstimateController@downloadBookingEstimateInvoice');
    Route::get('downloadMultipleBookingEstimateInvoice', 'BookingEstimateController@downloadMultipleBookingEstimateInvoice');
    /**Download season ticket invoice */
    Route::get('downloadSeasonTicketInvoice/{id}', 'SeasonTicketController@downloadSeasonTicketInvoice');

    /**Download cancellation invoice */
    Route::get('generateCancellationReceipt/{id}', 'BookingProcessController@generateCancellationReceipt');

    /**Download consolidated invoice */
    Route::get('generateConsolidatedInvoice/{id}', 'ConsolidatedInvoiceController@generateConsolidatedReceipt');

    /** */
    Route::get('exportReceiptBybelegUuidToObono', 'BookingProcessController@exportReceiptBybelegUuidToObono');

    //Ski path : Map & Video URL
    Route::get('/skiPathUrls', 'Book2SkiController@skiPathUrls');
});

require_once('book2ski.php');
require_once('openfire.php');
require_once('concardis.php');

// Route::get('payroll/payslip/email/{id}', 'API\PayrollController@emailPayslip');
