<?php

namespace App\Http\Controllers\API;

use App\User;
use DateTime;
use Notification;
use App\MeetingPoint;
use App\Models\Office;
use App\Models\Contact;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Jobs\UpdateChatRoom;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\ContactAddress;
use App\Models\ContactAllergy;
use App\Models\Courses\Course;
use App\Models\SequenceMaster;
use App\Models\ContactLanguage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\SeasonDaytimeMaster;
use App\Notifications\SendPassword;
use App\Http\Controllers\Controller;
use App\Models\Courses\CourseDetail;
use Illuminate\Support\Facades\Hash;
use App\Models\Courses\CourseCategory;
use App\Models\ContactAdditionalPerson;
use App\Notifications\CreateMultipleBooking;
use App\Models\Courses\CourseDifficultyLevel;
use App\Models\BookingProcess\BookingProcesses;
use App\Http\Requests\SkiMultipleBookingRequest;
use App\Models\BookingProcess\BookingProcessSource;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessLanguageDetails;
use App\Models\BookingProcess\BookingProcessExtraParticipant;

class Book2SkiController extends Controller
{
    use Functions;

    /* Get auth token from username and password */
    public function getToken(Request $request)
    {
        $v = validator($request->all(), [
            'username' => 'required',
            'password'      => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::where('email', $request->username)->first();

        if (!$user) {
            return $this->sendResponse(false, __('strings.invalid_credential'));
        }
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.incorrect_password'));
        }
        if (!$user->is_active) {
            return $this->sendResponse(false, __('strings.account_disabled'));
        }

        $accessTokenObject = $user->createToken('ThirdPartyLogin');
        $data = [
            'token' => $accessTokenObject->accessToken,
        ];
        return $this->sendResponse(true, 'Token generated successfully', $data);
    }

    /* Get course price list */
    public function getCourseCategory()
    {
        $categories = CourseCategory::select('id', 'name', 'name_en')->where('is_active', true)->get();
        return $this->sendResponse(true, 'Course category list', $categories);
    }

    /* Get course price list */
    public function getPriceList()
    {
        $course = Course::select('id', 'name', 'name_en', 'type', 'maximum_participant', 'maximum_instructor', 'is_feature_course', 'notes', 'notes_en', 'category_id', 'difficulty_level', 'course_banner', 'cal_payment_type', 'start_time','end_time','meeting_point_id', 'restricted_no_of_days', 'restricted_start_time', 'restricted_end_time', 'restricted_no_of_hours', 'price_per_item', 'is_include_lunch_hour')->where('is_active', true)
            ->where('is_archived', 0) //For only get unarchived courses
            ->where('is_display_on_website', 1); //For only get with website courses

        if (isset($_GET['category_id'])) {
            $course = $course->where('category_id', trim($_GET['category_id']));
        }
        if (isset($_GET['name'])) {
            $course = $course->where('name', trim($_GET['name']));
        }
        if (isset($_GET['type'])) {
            $course = $course->where('type', trim($_GET['type']));
        }
        if (isset($_GET['is_feature_course'])) {
            $course = $course->where('is_feature_course', trim($_GET['is_feature_course']));
        }
        $course = $course
            ->with(['course_detail' => function ($q) {
                $q->select('id', 'course_id', 'session', 'time', 'hours_per_day', 'price_per_day', 'no_of_days', 'extra_person_charge', 'cal_payment_type', 'is_include_lunch', 'include_lunch_price', 'total_price');
            }])
            ->with(['category_detail' => function ($q) {
                $q->select('id', 'name', 'name_en');
            }])
            ->with(['difficulty_level_detail' => function ($q) {
                $q->select('id', 'name');
            }])
            ->get();
        return $this->sendResponse(true, 'Course price list', $course);
    }

    public function checkCustomerEmail(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contact =  Contact::select('id', 'email', 'salutation', 'first_name', 'middle_name', 'last_name')->where('email', $request->email)->first();

        if (!$contact) {
            return $this->sendResponse(false, 'Email does not exist');
        }
        return $this->sendResponse(true, 'Customer exist', $contact);
    }

    /* Create new customer with details*/
    public function createCustomerWithDetails(Request $request)
    {
        $v = validator($request->all(), [
            'salutation' => 'required|in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'first_name' => 'required|max:250',
            'middle_name' => 'nullable|max:250',
            'last_name' => 'required|max:250',
            'email' => 'required|email|unique:contacts',
            'mobile1' => 'required|max:25',
            'mobile2' => 'max:25',
            'nationality' => 'required|max:50',
            //'designation'=>'max:50',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            'dob' => 'required|date_format:Y-m-d',
            'gender' => 'required|in:M,F,O',
            //'profile_pic' => '',
            'display_in_app' => 'boolean',
            // 'allergies' => 'required|array',

            // 'languages' => 'required|array',
            'allergies' => 'sometimes|array|exists:allergies,id',
            'languages' => 'required|array|exists:languages,id',
            'address' => 'required|array',
            'address.*.type' => 'in:L,P',
            'address.*.street_address1' => 'max:250',
            'address.*.city' => 'max:50',
            'address.*.state' => 'max:50',
            'address.*.country' => 'nullable|integer|min:1',

            'addition_persons' => 'array',
            'addition_persons.*.salutation' => 'in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'addition_persons.*.name' => 'max:50',
            //'addition_persons.*.relationaship'=>'max:20|in:Spouse,Father,Mother,Brother,Sister,Relative',
            'addition_persons.*.relationship' => 'max:20|in:Spouse,Father,Mother,Brother,Sister,Relative',
            'addition_persons.*.mobile1' => 'max:25',
            'addition_persons.*.mobile2' => 'max:25',
            'addition_persons.*.comments' => 'max:500',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input = $request->all();
        $input['category_id'] = 1;
        $input['created_by'] = auth()->user()->id;
        if ($request->profile_pic) {
            $url = $this->uploadImage('contacts', $request->first_name, $request->profile_pic);
            $input['profile_pic'] = $url;
        }
        $input['QR_number'] = mt_rand(100000000, 999999999);
        $contact = Contact::create($input);
        if ($request->display_in_app) {
            //create customer as user with email send to notification
            $userData = [];
            $userData['email'] = $request->email;
            $password = Str::random(6);
            $userData['password'] = Hash::make($password);
            $userData['contact_id'] = $contact->id;
            $userData['is_app_user'] = 1;
            $userData['is_verified'] = 1;
            $userData['email_token'] = '';
            $userData['device_token'] = '';
            $userData['device_type'] = '';
            $userData['name'] =  $contact->salutation . ' ' . $contact->first_name . ' ' . $contact->last_name;
            $user = User::create($userData);
        }

        if ($request->addition_persons) {
            foreach ($request->addition_persons as $person) {
                if (!is_array($person)) {
                    $person = json_decode($person, true);
                }
                $relationship = !empty($person['relationship']) ? $person['relationship'] : '';
                ContactAdditionalPerson::create([
                    'contact_id' => $contact->id,
                    'relationaship' => $relationship
                ] + $person);
            }
        }

        if ($request->allergies) {
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                    'contact_id' => $contact->id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if ($request->languages) {
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                    'contact_id' => $contact->id,
                    'language_id' => $language
                ]);
            }
        }

        if ($request->address) {
            foreach ($request->address as $address) {
                if (!is_array($address)) {
                    $address = json_decode($address, true);
                }
                ContactAddress::create([
                    'contact_id' => $contact->id,
                ] + $address);
            }
        }

        if ($request->display_in_app) {
            // $user->notify(new SendPassword($password));
            $user->notify((new SendPassword($password))->locale($user->language_locale));
        }
        
        $data = [
            'id' => $contact['id'],
            'salutation' => $contact['salutation'],
            'first_name' => $contact['first_name'],
            'middle_name' => $contact['middle_name'],
            'last_name' => $contact['last_name'],
            'email' => $contact['email'],
        ];
        return $this->sendResponse(true, 'Customer created successfully', $data);
    }

    /* Create new booking */
    public function createBooking(Request $request)
    {
        $v = validator($request->all(), [

            'course_type' => 'required|in:Private,Group',
            'course_id' => 'required|integer',
            'no_of_participant' => 'required|integer',
            'meeting_point_id' => 'sometimes|exists:meeting_points,id',
            'meeting_point' => 'nullable',
            'meeting_point_lat' => 'nullable',
            'meeting_point_long' => 'nullable',
            'lead' => 'sometimes|exists:categories,id',
            'contact_id' => 'sometimes|exists:contacts,id',
            'source_id' => 'sometimes|exists:booking_process_sources,id',
            'note' => 'nullable',
            'difficulty_level' => 'required|exists:course_difficulty_levels,id',
            'payee_id' => 'required|integer|exists:contacts,id,category_id,1',
            //'additional_note' => 'max:200',

            'customer_detail' => 'required|array',
            // 'customer_detail.*.customer_id' => 'integer|exists:contacts,id',
            // 'customer_detail.*.course_detail_id' => 'integer|min:1|exists:course_details,id',
            // 'customer_detail.*.start_date' => 'required|date_format:Y-m-d',
            // 'customer_detail.*.end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            // 'customer_detail.*.start_time' => 'required|date_format:H:i:s',
            // 'customer_detail.*.end_time' => 'required|date_format:H:i:s|after_or_equal:start_time',

            'language_detail' => 'array|exists:languages,id',
            'extra_participants_details' => 'array',

            'is_pay' => 'boolean',
            'payment_method' => 'required_if:is_pay,==,1|in:C,BT,S,P,O,CC',
            'discount' => 'numeric|min:0|max:100',

        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $authUserId = auth()->user()->id;
        $payment_method = $request->payment_method;
        $payment_method_id = null;
        $checkPayment =  PaymentMethod::where('type', $request->payment_method)->first();
        if ($checkPayment) {
            $payment_method_id = $checkPayment->id;
        }

        // Insert into main booking table
        $bookingInput = [];
        $bookingInput['is_draft'] = true;
        $bookingInput['created_by'] = $authUserId;
        $bookingInput['QR_number'] = mt_rand(100000000, 999999999);
        // if ($request->is_lunch_included) {
        //     $checkLunch = CourseDetail::where('course_id', $request->course_id)->where('is_include_lunch', 0)->count();
        //     if ($checkLunch) {
        //         return $this->sendResponse(false, __('strings.lunch_price_validation'));
        //     }
        //     $bookingInput['note'] = __('strings.lunch_include_comment', ['value' => 20]);
        // }
        $bookingInput['payi_id'] = $request->payee_id;
        $bookingInput['is_third_party'] = true;
        $bookingInput['note'] = $request->note;
        $booking_number = SequenceMaster::where('code', 'BN')->first();
        if ($booking_number) {
            $bookingInput['booking_number'] = "EL" . date("m") . "" . date("Y") . $booking_number->sequence;
            $booking_number->update(['sequence' => $booking_number->sequence + 1]);
        }
        $booking = BookingProcesses::create($bookingInput);

        // Insert into booking languages
        if ($request->language_detail) {
            $language_detail_inputs['created_by'] = $authUserId;
            $language_detail_inputs['booking_process_id'] = $booking->id;
            foreach ($request->language_detail as $key => $language_detail) {
                $language_detail_inputs['language_id'] = $language_detail;
                $language_detail = BookingProcessLanguageDetails::create($language_detail_inputs);
            }
        }

        // Insert into booking extra participant
        if ($request->extra_participants_details) {
            foreach ($request->extra_participants_details as $key => $extra_participants_detail) {
                if (!is_array($extra_participants_detail)) {
                    $extra_participants_detail = json_decode($extra_participants_detail, true);
                }
                $extra_participants_inputs = $extra_participants_detail;
                $extra_participants_inputs['created_by'] = $authUserId;
                $extra_participants_inputs['booking_process_id'] = $booking->id;
                $instructor_detail_inputs = BookingProcessExtraParticipant::create($extra_participants_inputs);
            }
        }

        //insert into booking customers
        $courseStartDate = $courseEndDate = '';
        $total_include_lunch_price = 0;
        foreach ($request->customer_detail as $key => $customer_detail) {
            if (!is_array($customer_detail)) {
                $customer_detail = json_decode($customer_detail, true);
            }
            $course_detail = CourseDetail::find($customer_detail['course_detail_id']);
            if ($course_detail) {
                $customer_detail_inputs = $customer_detail;
                $customer_detail_inputs['payi_id'] = $request->payee_id;
                $customer_detail_inputs['StartDate_Time'] = $customer_detail['start_date'] . ' ' . $customer_detail['start_time'];
                $customer_detail_inputs['EndDate_Time'] = $customer_detail['end_date'] . ' ' . $customer_detail['end_time'];

                if ($courseStartDate == '') {
                    $courseStartDate = $customer_detail_inputs['StartDate_Time'];
                } elseif ($customer_detail_inputs['StartDate_Time'] < $courseStartDate) {
                    $courseStartDate = $customer_detail_inputs['StartDate_Time'];
                }
                if ($courseEndDate == '') {
                    $courseEndDate = $customer_detail_inputs['EndDate_Time'];
                } elseif ($customer_detail_inputs['EndDate_Time'] < $courseEndDate) {
                    $courseEndDate = $customer_detail_inputs['EndDate_Time'];
                }

                $customer_detail_inputs['created_by'] = $authUserId;
                $customer_detail_inputs['no_of_days'] = $course_detail['no_of_days'];
                $customer_detail_inputs['hours_per_day'] = $course_detail['hours_per_day'];
                $customer_detail_inputs['booking_process_id'] = $booking->id;
                $customer_detail_inputs['is_payi'] = $customer_detail['customer_id'] === $request->payee_id ? 'Yes' : 'No';
                $customer_detail_inputs['QR_number'] = $booking->id . $customer_detail_inputs['customer_id'] . mt_rand(100000, 999999);
                $bookingCustomer = BookingProcessCustomerDetails::create($customer_detail_inputs);
                if ($request->is_pay) {
                    $this->createInvoice($bookingCustomer, $payment_method_id, $course_detail, $request);
                }
                if ($course_detail['is_include_lunch']) {
                    $total_include_lunch_price = $total_include_lunch_price + $course_detail['include_lunch_price'];
                }
            }
        }

        if ($total_include_lunch_price) {
            $booking->note = __('strings.lunch_include_comment', ['value' => $total_include_lunch_price]);
            $booking->save();
        }

        if (!$courseStartDate || !$courseEndDate) {
            return $this->sendResponse(false, "customer detail is invalid");
        }

        //Insert into booking course detail
        $course_detail_inputs = [];
        $course_detail_inputs['booking_process_id'] = $booking->id;
        $course_detail_inputs['course_type'] = $request->course_type;
        $course_detail_inputs['course_id'] = $request->course_id;
        $course_detail_inputs['StartDate_Time'] = $courseStartDate;
        $course_detail_inputs['EndDate_Time'] = $courseEndDate;
        $courseStartDateArray = explode(" ", $courseStartDate);
        $courseEndDateArray = explode(" ", $courseEndDate);
        if (count($courseStartDateArray) > 1 && count($courseEndDateArray) > 1) {
            $course_detail_inputs['start_date'] = $courseStartDateArray[0];
            $course_detail_inputs['end_date'] = $courseEndDateArray[0];
            $course_detail_inputs['start_time'] = $courseStartDateArray[1];
            $course_detail_inputs['end_time'] = $courseEndDateArray[1];
        }

        $course_detail_inputs['no_of_participant'] = $request->no_of_participant;
        $course_detail_inputs['meeting_point_id'] = $request->meeting_point_id;
        $course_detail_inputs['meeting_point'] = $request->meeting_point;
        $course_detail_inputs['meeting_point_lat'] = $request->meeting_point_lat;
        $course_detail_inputs['meeting_point_long'] = $request->meeting_point_long;
        $course_detail_inputs['created_by'] = $authUserId;

        //comment after implement dynamic parameter source in create booking api
        // $source =  BookingProcessSource::where('type', 'B2S')->first();
        // if ($source) {
        //     $course_detail_inputs['source_id'] = $source->id;
        // }

        $course_detail_inputs['source_id'] = $request->source_id;
        $course_detail_inputs['lead'] = $request->lead;
        $course_detail_inputs['contact_id'] = $request->contact_id;
        $course_detail_inputs['difficulty_level'] = $request->difficulty_level;

        BookingProcessCourseDetails::create($course_detail_inputs);

        $data = [
            'id' => $booking['id'],
            'booking_number' => $booking['booking_number'],
        ];

        //Update chatroom for openfire
        UpdateChatRoom::dispatch(true, $booking['id']);
        return $this->sendResponse(true, 'Booking created successfully', $data);
    }

    public function viewBooking($booking_number)
    {
        $booking = BookingProcesses::select('id', 'booking_number', 'note', 'payi_id as payee_id')
            ->with(['course_detail' => function ($q) {
                $q->select('id', 'booking_process_id', 'course_type', 'course_id', 'no_of_participant', 'meeting_point');
            }])
            ->with(['customer_detail' => function ($q) {
                $q->select('id', 'booking_process_id', 'customer_id', 'course_detail_id', 'start_date', 'end_date', 'start_time', 'end_time');
            }])
            ->with(['extra_participant_detail' => function ($q) {
                $q->select('id', 'booking_process_id', 'name', 'age');
            }])
            ->with(['language_detail' => function ($q) {
                $q->select('id', 'booking_process_id', 'language_id');
            }])
            ->where('booking_number', $booking_number)->first();

        if (!$booking) {
            return $this->sendResponse(false, 'Invalid booking number');
        }
        return $this->sendResponse(true, 'Booking detail', $booking);
    }

    /**For calculate normal course invoice details */
    public function createInvoice($customerDetail, $payment_method_id, $courseDetail, $booking_details)
    {
        $payment_detail_inputs = [];
        $payment_detail_inputs['created_by'] = auth()->user()->id;
        $payment_detail_inputs['customer_id'] = $customerDetail['customer_id'];
        $payment_detail_inputs['payi_id'] = $customerDetail['payi_id'];
        $payment_detail_inputs['booking_process_id'] = $customerDetail['booking_process_id'];
        $payment_detail_inputs['booking_process_customer_detail_id'] = $customerDetail['id'];
        $payment_detail_inputs['payment_method_id'] = $payment_method_id;
        $payment_detail_inputs['course_detail_id'] = $courseDetail['id'];
        $payment_detail_inputs['status'] = 'Pending';
        $payment_detail_inputs['discount'] = $booking_details['discount'];

        $payment_detail_inputs['no_of_days'] = $courseDetail->no_of_days;
        $payment_detail_inputs['hours_per_day'] = $courseDetail->hours_per_day;
        $payment_detail_inputs['cal_payment_type'] = $courseDetail->cal_payment_type;

        $price_per_day = $courseDetail->price_per_day;
        $total_price = $price_per_day;
        $payment_detail_inputs['total_price'] = $total_price;

        if ($booking_details['course_type'] === 'Private') {
            $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $customerDetail['booking_process_id'])->count();
            $extra_person_charge = 0;
            if ($extra_participant) {
                $extra_person_charge = $courseDetail->extra_person_charge;
            }
            $payment_detail_inputs['extra_person_charge'] = $extra_person_charge;
            $payment_detail_inputs['extra_participant'] = $extra_participant * $extra_person_charge;
            $total_price1 = $total_price + ($extra_participant * $extra_person_charge);
            $netPrice = $total_price1 - ($total_price1 * ($payment_detail_inputs['discount'] / 100));
        } else {
            $netPrice = $total_price - ($total_price * ($payment_detail_inputs['discount'] / 100));
        }

        $vat_percentage = $this->getVatValue();
        $vat_excluded_amount = $netPrice / ((100 + $vat_percentage) / 100);
        $vat_amount = $netPrice - $vat_excluded_amount;
        $payment_detail_inputs['vat_percentage'] = $vat_percentage;
        $payment_detail_inputs['vat_amount'] = $vat_amount;
        $payment_detail_inputs['vat_excluded_amount'] = $vat_excluded_amount;

        if (isset($booking_details['is_lunch_included'])) {
            $payment_detail_inputs['is_include_lunch'] = true;
            $payment_detail_inputs['include_lunch_price'] = $courseDetail['include_lunch_price'];

            $lunch_vat_percentage = $this->getLunchVat();
            $payment_detail_inputs['lunch_vat_percentage'] = $lunch_vat_percentage;

            $lunch_vat_excluded_amount = $courseDetail['include_lunch_price'] / ((100 + $lunch_vat_percentage) / 100);
            $lunch_vat_amount = $courseDetail['include_lunch_price'] - $lunch_vat_excluded_amount;
            $payment_detail_inputs['lunch_vat_amount'] = $lunch_vat_amount;
            $payment_detail_inputs['lunch_vat_excluded_amount'] = $lunch_vat_excluded_amount;

            //Include lunch price in net price
            $netPrice += $courseDetail['include_lunch_price'];
        }
        $payment_detail_inputs['net_price'] = $netPrice;
        $payment_detail_inputs['outstanding_amount'] = $netPrice;

        $invoice_number = SequenceMaster::where('code', 'INV')->first();

        if ($invoice_number) {
            $payment_detail_inputs['invoice_number'] = "INV" . date("m") . "" . date("Y") . $invoice_number->sequence;
            $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
        }

        $invoice = BookingProcessPaymentDetails::create($payment_detail_inputs);

        //Get office id for create payment
        $office = Office::where('is_head_office', '1')->first();
        if ($office) {
            $office_id = $office->id;
        }

        //Insert data into booking_payments
        $payment_details = [
            "qbon_number"               => null,
            "contact_id"                => $customerDetail['payi_id'],
            "payment_type"              => $payment_method_id,
            "is_office"                 => 1,
            "office_id"                 => $office_id,
            "amount_given_by_customer"  => $netPrice,
            "amount_returned"           => 0,
            "total_amount"              => $total_price,
            "total_discount"            => $booking_details['discount'] ?? 0,
            "total_vat_amount"          => $vat_amount,
            "total_net_amount"          => $netPrice,
            "total_lunch_amount"        => (isset($courseDetail['include_lunch_price']) ? $courseDetail['include_lunch_price'] : 0),
        ];
        $invoice_data[]['id'] = $invoice['id'];
        DB::beginTransaction();
        try {
            // Create payment
            $payment = $this->createPayment($payment_details, $invoice_data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            echo $this->sendResponse(false, $e->getMessage());
            exit;
        }
    }
    /**For calculate other course invoice details */
    public function createInvoiceOtherCourse($customerDetail, $course, $payment_method_id, $discount)
    {
        $payment_detail_inputs = [];
        $payment_detail_inputs['created_by'] = auth()->user()->id;
        $payment_detail_inputs['customer_id'] = $customerDetail['customer_id'];
        $payment_detail_inputs['payi_id'] = $customerDetail['payi_id'];
        $payment_detail_inputs['booking_process_id'] = $customerDetail['booking_process_id'];
        $payment_detail_inputs['booking_process_customer_detail_id'] = $customerDetail['id'];
        $payment_detail_inputs['payment_method_id'] = $payment_method_id;
        // $payment_detail_inputs['course_detail_id'] = $courseDetail['id'];
        $payment_detail_inputs['status'] = 'Pending';
        $payment_detail_inputs['discount'] = $discount;

        $payment_detail_inputs['no_of_days'] = $customerDetail['no_of_days'];
        $payment_detail_inputs['hours_per_day'] = $customerDetail['hours_per_day'];
        $payment_detail_inputs['cal_payment_type'] = $course->cal_payment_type;

        $price_per_item = $course->price_per_item;
        $total_price = $price_per_item;
        $payment_detail_inputs['total_price'] = $total_price;

        $netPrice = $total_price - ($total_price * ($payment_detail_inputs['discount'] / 100));

        $vat_percentage = $this->getVatValue();
        $vat_excluded_amount = $netPrice / ((100 + $vat_percentage) / 100);
        $vat_amount = $netPrice - $vat_excluded_amount;
        $payment_detail_inputs['vat_percentage'] = $vat_percentage;
        $payment_detail_inputs['vat_amount'] = $vat_amount;
        $payment_detail_inputs['vat_excluded_amount'] = $vat_excluded_amount;
        $payment_detail_inputs['net_price'] = $netPrice;
        $payment_detail_inputs['outstanding_amount'] = $netPrice;
        $invoice_number = SequenceMaster::where('code', 'INV')->first();

        if ($invoice_number) {
            $payment_detail_inputs['invoice_number'] = "INV" . date("m") . "" . date("Y") . $invoice_number->sequence;
            $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
        }

        $invoice = BookingProcessPaymentDetails::create($payment_detail_inputs);

        //Get office id for create payment
        $office = Office::where('is_head_office', '1')->first();
        if ($office) {
            $office_id = $office->id;
        }

        //Insert data into booking_payments
        $payment_details = [
            "qbon_number"               => null,
            "contact_id"                => $customerDetail['payi_id'],
            "payment_type"              => $payment_method_id,
            "is_office"                 => 1,
            "office_id"                 => $office_id,
            "amount_given_by_customer"  => $netPrice,
            "amount_returned"           => 0,
            "total_amount"              => $total_price,
            "total_discount"            => $discount ?? 0,
            "total_vat_amount"          => $vat_amount,
            "total_net_amount"          => $netPrice
        ];
        $invoice_data[]['id'] = $invoice['id'];
        DB::beginTransaction();
        try {
            // Create payment
            $payment = $this->createPayment($payment_details, $invoice_data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            echo $this->sendResponse(false, $e->getMessage());
            exit;
        }
    }
    /* Ski path : Map & Video URL */
    public function skiPathUrls()
    {
        $skiMap = [
            [
                'season' => 'Sommer',
                'url' => 'https://map.kitzski.at/de/sommer/',
            ],
            [
                'season' => 'Winter',
                'url' => 'https://map.kitzski.at/de/winter/',
            ],

        ];

        $webCams = [
            [
                'title' => 'JochbergWagstätt Bergstation, 1750 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/jochberg.html',
                'videoUrl' => ''
            ],
            [
                'title' => '​​Jochberg Funpark Hanglalm, 1810 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/jochberg-funpark-hanglalm.html',
                'videoUrl' => ''
            ],
            [
                'title' => 'Kitzbühel/Kirchberg Pengelstein, 1940 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/kirchberg.html',
                'videoUrl' => ''
            ],
            [
                'title' => '​​​​Kirchberg - Ochsalm 1480 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/kirchberg-ochsalm.html',
                'videoUrl' => ''
            ],
            [
                'title' => '​​​​Kitzbühel Kitzbüheler Hornköpfl, 1780 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/kitzbuehel-kitzbueheler-hornkoepfl.html',
                'videoUrl' => ''
            ],
            [
                'title' => '​​​​Kitzbühel Hahnenkamm Berg, 1665 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/kitzbuehel-hahnenkamm-berg.html',
                'videoUrl' => ''
            ],
            [
                'title' => 'Kitzbühel - Speicherteich Hornköpfl 1710 m',
                'webUrl' => 'https://www.feratel.com/webcams/oesterreich/tirol/kitzbuehel-speicherteich-hornkoepfl.html​',
                'videoUrl' => ''
            ],
        ];

        $data = [
            'skiMap' => $skiMap,
            'webCams' => $webCams,
        ];
        return response()->json(['message' => 'Ski path : Map & Video URL', 'skiMap' => $skiMap, 'webCams' => $webCams], 200);
    }

    /**Get meeting point list */
    public function meetingPointList(Request $request)
    {

        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
        }

        $meeting_point = MeetingPoint::select('id', 'name', 'address', 'lat', 'long', 'is_active');
        $meeting_point_count = $meeting_point->count();

        if ($request->search) {
            $search = $request->search;
            $meeting_point = $meeting_point->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            });
            $meeting_point_count = $meeting_point->count();
        }

        if ($request->is_active) {
            $meeting_point = $meeting_point->where('is_active', 1);
            $meeting_point_count = $meeting_point->count();
        }

        if ($request->page && $request->perPage) {
            $meeting_point->skip($perPage * ($page - 1))->take($perPage);
        }

        $meeting_point = $meeting_point->latest()->get();

        $data = [
            'meeting_point' => $meeting_point,
            'count' => $meeting_point_count
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /**Get Booking Sources list */
    public function bookingSourcesList()
    {

        $source = BookingProcessSource::get();
        return $this->sendResponse(true, 'success', $source);
    }

    /**Get Leads list */
    public function leadsList()
    {

        //$categories = Category::with('subcategories')->get();
        $categories = Category::get();
        return $this->sendResponse(true, 'success', $categories);
    }

    /**Get Courses Difficulty Level list */
    public function DifficultyLevelList()
    {

        $difficultyLevel = CourseDifficultyLevel::get();
        return $this->sendResponse(true, __('success'), $difficultyLevel);
    }

    /** Get Active Contact List Search By Category For Lead Contact  */
    public function leadsContactList()
    {
        $contacts = Contact::query();

        $contacts = $contacts->where('is_active', '1');

        if (isset($_GET['category_id'])) {
            $contacts = $contacts->where('category_id', $_GET['category_id']);
        }

        if (isset($_GET['display_in_app'])) {
            $contacts = $contacts->where('display_in_app', $_GET['display_in_app']);
        }

        if (isset($_GET['subcategory_id'])) {
            $contacts = $contacts->where('subcategory_id', $_GET['subcategory_id']);
        }

        if (isset($_GET['search'])) {
            $search = $_GET['search'];
            $contacts = $contacts->where(function ($query) use ($search) {
                $query->where('email', 'like', "%$search%");
                $query->orWhere('mobile1', 'like', "%$search%");
                $query->orWhere('salutation', 'like', "%$search%");
                $query->orWhere('first_name', 'like', "%$search%");
                $query->orWhere('middle_name', 'like', "%$search%");
                $query->orWhere('last_name', 'like', "%$search%");
            });
        }

        $contacts = $contacts->with(['payment_method'])
            ->with('subcategory_detail')
            ->with(['allergies' => function ($q) {
                $q->with('allergy:id,name');
            }])->get();
        return $this->sendResponse(true, 'success', $contacts);
    }

    /* Create new multiple booking */
    public function createMultipleBooking(SkiMultipleBookingRequest $request)
    {
        $authUserId = auth()->user()->id;
        foreach ($request->booking_details as $booking_details) {
            $payment_method = $booking_details['payment_method'];
            $payment_method_id = null;
            $checkPayment =  PaymentMethod::where('type', $booking_details['payment_method'])->first();
            if ($checkPayment) {
                $payment_method_id = $checkPayment->id;
            }

            // Insert into main booking table
            $bookingInput = [];
            $bookingInput['is_draft'] = true;
            $bookingInput['created_by'] = $authUserId;
            $bookingInput['QR_number'] = mt_rand(100000000, 999999999);
            // if (isset($booking_details['is_lunch_included'])) {
            //     $checkLunch = CourseDetail::where('course_id', $booking_details['course_id'])->where('is_include_lunch', 0)->count();
            //     if ($checkLunch) {
            //         return $this->sendResponse(false, __('strings.lunch_price_validation'));
            //     }
            //     // $bookingInput['note'] = __('strings.lunch_include_comment', ['value' => 20]);
            // }
            $bookingInput['payi_id'] = $booking_details['payee_id'];
            $bookingInput['is_third_party'] = true;
            $bookingInput['note'] = $booking_details['note'];
            $booking_number = SequenceMaster::where('code', 'BN')->first();
            if ($booking_number) {
                $bookingInput['booking_number'] = "EL" . date("m") . "" . date("Y") . $booking_number->sequence;
                $booking_number->update(['sequence' => $booking_number->sequence + 1]);
            }

            $booking = BookingProcesses::create($bookingInput);

            // Insert into booking languages
            if (isset($booking_details['language_detail'])) {
                $language_detail_inputs['created_by'] = $authUserId;
                $language_detail_inputs['booking_process_id'] = $booking->id;
                foreach ($booking_details['language_detail'] as $key => $language_detail) {
                    $language_detail_inputs['language_id'] = $language_detail;
                    $language_detail = BookingProcessLanguageDetails::create($language_detail_inputs);
                }
            }

            // Insert into booking extra participant
            if (isset($booking_details['extra_participants_details'])) {
                foreach ($booking_details['extra_participants_details'] as $key => $extra_participants_detail) {
                    if (!is_array($extra_participants_detail)) {
                        $extra_participants_detail = json_decode($extra_participants_detail, true);
                    }
                    $extra_participants_inputs = $extra_participants_detail;
                    $extra_participants_inputs['created_by'] = $authUserId;
                    $extra_participants_inputs['booking_process_id'] = $booking->id;
                    $instructor_detail_inputs = BookingProcessExtraParticipant::create($extra_participants_inputs);
                }
            }

            //insert into booking customers
            $courseStartDate = $courseEndDate = '';
            $total_include_lunch_price = 0;
            $is_include_lunch_hour = false;//For check include lunch hour

            foreach ($booking_details['customer_detail'] as $key => $customer_detail) {
                if (!is_array($customer_detail)) {
                    $customer_detail = json_decode($customer_detail, true);
                }
                $course = Course::find($booking_details['course_id']);
                if (isset($customer_detail['course_detail_id']) && $course->type != 'Other') {
                    $course_detail = CourseDetail::find($customer_detail['course_detail_id']);
                    if ($course_detail) {
                        $customer_detail_inputs = $customer_detail;
                        $customer_detail_inputs['payi_id'] = $booking_details['payee_id'];
                        $customer_detail_inputs['StartDate_Time'] = $customer_detail['start_date'] . ' ' . $customer_detail['start_time'];
                        $customer_detail_inputs['EndDate_Time'] = $customer_detail['end_date'] . ' ' . $customer_detail['end_time'];

                        if ($courseStartDate == '') {
                            $courseStartDate = $customer_detail_inputs['StartDate_Time'];
                        } elseif ($customer_detail_inputs['StartDate_Time'] < $courseStartDate) {
                            $courseStartDate = $customer_detail_inputs['StartDate_Time'];
                        }
                        if ($courseEndDate == '') {
                            $courseEndDate = $customer_detail_inputs['EndDate_Time'];
                        } elseif ($customer_detail_inputs['EndDate_Time'] < $courseEndDate) {
                            $courseEndDate = $customer_detail_inputs['EndDate_Time'];
                        }

                        $customer_detail_inputs['created_by'] = $authUserId;
                        $customer_detail_inputs['no_of_days'] = $course_detail['no_of_days'];
                        $customer_detail_inputs['hours_per_day'] = $course_detail['hours_per_day'];
                        $customer_detail_inputs['booking_process_id'] = $booking->id;
                        $customer_detail_inputs['cal_payment_type'] = $course->cal_payment_type;
                        $customer_detail_inputs['is_payi'] = $customer_detail['customer_id'] === $booking_details['payee_id'] ? 'Yes' : 'No';
                        $customer_detail_inputs['QR_number'] = $booking->id . $customer_detail_inputs['customer_id'] . mt_rand(100000, 999999);
                        $customer_detail_inputs['is_include_lunch_hour'] = ($customer_detail['is_include_lunch_hour']?:false);

                        $bookingCustomer = BookingProcessCustomerDetails::create($customer_detail_inputs);
                        if ($booking_details['is_pay']) {
                            $this->createInvoice($bookingCustomer, $payment_method_id, $course_detail, $booking_details);
                        }
                        if ($course_detail['is_include_lunch']) {
                            $total_include_lunch_price = $total_include_lunch_price + $course_detail['include_lunch_price'];
                        }
                    }
                } else {
                    $customer_detail_inputs = $customer_detail;
                    $customer_detail_inputs['payi_id'] = $booking_details['payee_id'];
                    $customer_detail_inputs['StartDate_Time'] = $customer_detail['start_date'] . ' ' . $customer_detail['start_time'];
                    $customer_detail_inputs['EndDate_Time'] = $customer_detail['end_date'] . ' ' . $customer_detail['end_time'];
                    $customer_detail_inputs['course_detail_id'] = 0;

                    if ($courseStartDate == '') {
                        $courseStartDate = $customer_detail_inputs['StartDate_Time'];
                    } elseif ($customer_detail_inputs['StartDate_Time'] < $courseStartDate) {
                        $courseStartDate = $customer_detail_inputs['StartDate_Time'];
                    }
                    if ($courseEndDate == '') {
                        $courseEndDate = $customer_detail_inputs['EndDate_Time'];
                    } elseif ($customer_detail_inputs['EndDate_Time'] < $courseEndDate) {
                        $courseEndDate = $customer_detail_inputs['EndDate_Time'];
                    }

                    $customer_detail_inputs['created_by'] = $authUserId;

                    /**Calculate no of days and hours */
                    $datetime1 = new DateTime($courseStartDate);
                    $datetime2 = new DateTime($courseEndDate);

                    $interval = $datetime1->diff($datetime2);
                    $days = $interval->format('%a') + 1;
                    $hours = $interval->format('%h');
                    /** */
                    $customer_detail_inputs['no_of_days'] = (int)$days;
                    $customer_detail_inputs['hours_per_day'] = (int)$hours;
                    $customer_detail_inputs['booking_process_id'] = $booking->id;
                    $customer_detail_inputs['cal_payment_type'] = $course->cal_payment_type;
                    $customer_detail_inputs['is_payi'] = $customer_detail['customer_id'] === $booking_details['payee_id'] ? 'Yes' : 'No';
                    $customer_detail_inputs['QR_number'] = $booking->id . $customer_detail_inputs['customer_id'] . mt_rand(100000, 999999);
                    $bookingCustomer = BookingProcessCustomerDetails::create($customer_detail_inputs);
                    if ($booking_details['is_pay']) {
                        $this->createInvoiceOtherCourse($bookingCustomer, $course, $payment_method_id, $booking_details['discount']);
                    }
                }
                if(isset($customer_detail['is_include_lunch_hour']) && $customer_detail['is_include_lunch_hour']){
                    $is_include_lunch_hour = true;
                }
            }

            if ($total_include_lunch_price) {
                $booking->note = __('strings.lunch_include_comment', ['value' => $total_include_lunch_price]);
                $booking->save();
            }

            if (!$courseStartDate || !$courseEndDate) {
                return $this->sendResponse(false, "customer detail is invalid");
            }

            //Insert into booking course detail
            $course_detail_inputs = [];
            $course_detail_inputs['booking_process_id'] = $booking->id;
            $course_detail_inputs['course_type'] = $booking_details['course_type'];
            $course_detail_inputs['course_id'] = $booking_details['course_id'];
            $course_detail_inputs['StartDate_Time'] = $courseStartDate;
            $course_detail_inputs['EndDate_Time'] = $courseEndDate;
            $courseStartDateArray = explode(" ", $courseStartDate);
            $courseEndDateArray = explode(" ", $courseEndDate);
            if (count($courseStartDateArray) > 1 && count($courseEndDateArray) > 1) {
                $course_detail_inputs['start_date'] = $courseStartDateArray[0];
                $course_detail_inputs['end_date'] = $courseEndDateArray[0];
                $course_detail_inputs['start_time'] = $courseStartDateArray[1];
                $course_detail_inputs['end_time'] = $courseEndDateArray[1];
            }

            $course_detail_inputs['no_of_participant'] = $booking_details['no_of_participant'];
            $course_detail_inputs['created_by'] = $authUserId;

            (isset($booking_details['meeting_point_id']) ? $course_detail_inputs['meeting_point_id'] = $booking_details['meeting_point_id'] : $course_detail_inputs['meeting_point_id'] = null);

            (isset($booking_details['meeting_point']) ? $course_detail_inputs['meeting_point'] = $booking_details['meeting_point'] : $course_detail_inputs['meeting_point'] = null);

            (isset($booking_details['meeting_point_lat']) ? $course_detail_inputs['meeting_point_lat'] = $booking_details['meeting_point_lat'] : $course_detail_inputs['meeting_point_lat'] = null);

            (isset($booking_details['meeting_point_long']) ? $course_detail_inputs['meeting_point_long'] = $booking_details['meeting_point_long'] : $course_detail_inputs['meeting_point_long'] = null);

            //comment after implement dynamic parameter source in create booking api
            // $source =  BookingProcessSource::where('type', 'B2S')->first();
            // if ($source) {
            //     $course_detail_inputs['source_id'] = $source->id;
            // }

            (isset($booking_details['extra_participants_details']) ? $course_detail_inputs['no_of_extra_participant'] = count($booking_details['extra_participants_details']) : $course_detail_inputs['no_of_extra_participant'] = null);

            (isset($booking_details['source_id']) ? $course_detail_inputs['source_id'] = $booking_details['source_id'] : $course_detail_inputs['source_id'] = null);

            (isset($booking_details['lead']) ? $course_detail_inputs['lead'] = $booking_details['lead'] : $course_detail_inputs['lead'] = null);

            (isset($booking_details['contact_id']) ? $course_detail_inputs['contact_id'] = $booking_details['contact_id'] : $course_detail_inputs['contact_id'] = null);

            (isset($booking_details['difficulty_level']) ? $course_detail_inputs['difficulty_level'] = $booking_details['difficulty_level'] : $course_detail_inputs['difficulty_level'] = null);

            /**Lunch hour related */
            $datetime1 = new DateTime($courseStartDate);
            $datetime2 = new DateTime($courseEndDate);

            $interval = $datetime2->diff($datetime1);
            $course_detail_inputs['total_days'] = $interval->format('%d') + 1;//Day -1 calucalte count so
            $course_detail_inputs['total_hours'] = $interval->format('%h');
            
            if($is_include_lunch_hour){
                $course_detail_inputs['lunch_hour'] = 1;//Lunch hour is fix 1 hour
            }
            /**End */
            
            BookingProcessCourseDetails::create($course_detail_inputs);

            //Send email to element3 admin for booking added notification
            $email_data = array();
            $booking_processes_id = $booking->id;
            $email_data['booking_number'] = $booking->booking_number;

            //Course Details
            $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes_id)->first();

            $email_data['start_date'] = $booking_processes_course_datail->start_date;
            $email_data['start_time'] = $booking_processes_course_datail->start_time;
            $email_data['end_date'] = $booking_processes_course_datail->end_date;
            $email_data['end_time'] = $booking_processes_course_datail->end_time;
            $email_data['course_type'] = $booking_processes_course_datail->course_type;

            $course_id = $booking_processes_course_datail->course_id;
            $course = Course::where('id', $course_id)->first();
            $course_name = ($course ? $course->name : null);

            Notification::route('mail' ,'info@element3.at')->notify(new CreateMultipleBooking($email_data, $course_name, $booking->booking_number));
            /**End */

            $data[] = [
                'id' => $booking['id'],
                'booking_number' => $booking['booking_number'],
            ];

            //Update chatroom for openfire
            UpdateChatRoom::dispatch(true, $booking['id']);
        }
        return $this->sendResponse(true, 'Booking created successfully', $data);
    }

    /**Customer login */
    public function customerLogin(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::where('email', $request->email)
            ->with(['contact_detail' => function ($query) {
                $query->select(
                    'id',
                    'category_id',
                    'salutation',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'mobile1',
                    'mobile2',
                    'nationality',
                    'designation',
                    'dob',
                    'gender',
                    'profile_pic',
                    'QR_number',
                    'is_active',
                    'contact_person_email',
                    'contact_person_name'
                );
            }])
            ->select('id', 'name', 'password', 'contact_id', 'is_active', 'is_notification')
            ->first();

        if (!$user) {
            return $this->sendResponse(false, __('strings.invalid_credential'));
        }

        $contact = Contact::where('category_id', 1)->find($user->contact_id);

        if (!$contact) {
            return $this->sendResponse(false, __('strings.invalid_credential'));
        }

        /**Check password is valid or not */
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.invalid_credential'));
        }

        /**Check logged in user active or not */
        if (!$user->is_active || !$contact->is_active) {
            return $this->sendResponse(false, __('strings.account_disabled'));
        }

        if ($user->is_app_user == 0 || $user->is_app_user == '') {
            $user->is_app_user = 1;
        }

        $user->save();

        $contact_address = ContactAddress::where('contact_id', $user->contact_id)->get();

        /**Check user profile full filled or not */
        $is_profile_fullfilled = 1;

        if (empty($contact->salutation) && empty($contact->mobile1) && empty($contact->nationality) && empty($contact->designation) && empty($contact->gender) && empty($contact->middle_name) && empty($contact_address->street_address1) && empty($contact_address->city) && empty($contact_address->state) && empty($contact_address->country)) {
            $is_profile_fullfilled = 0;
        }

        /**Revoke customer old tokens */
        $userTokens = $user->tokens;
        foreach ($userTokens as $token) {
            $token->revoke();
        }
        /**Remove token */
        unset($user->tokens);

        /**Create new login token */
        $accessTokenObject = $user->createToken('Login');
        $data = [
            'user_data' => $user,
            'is_profile_fullfilled' => $is_profile_fullfilled,
            'token' => $accessTokenObject->accessToken,
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /**Get customer invoice list */
    public function getInvoiceList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $booking_processes_ids = BookingProcessPaymentDetails::query();

        $contact_id = auth()->user()->contact_id;

        if (auth()->user()->type() === 'Customer') {
            //Show list where customer is payi
            $booking_processes_ids = $booking_processes_ids->where('payi_id', $contact_id);

            // show list where customer is payi OR customer.
            /* ->where(function ($query) use ($contact_id) {
                $query->where('customer_id', $contact_id);
                $query->orWhere('payi_id', $contact_id);
            }); */
        }

        $booking_processes_ids = $booking_processes_ids->orderBy('id', 'desc');
        if ($request->payment_status) {
            $booking_processes_ids = $booking_processes_ids->where('status', $request->payment_status);
        }

        if ($request->tax_consultant_status) {
            $booking_processes_ids = $booking_processes_ids->where('tax_consultant', $request->tax_consultant_status);
        }

        if ($request->date_range1 && $request->date_range2) {
            $booking_processes_ids = $booking_processes_ids->whereBetween('created_at', [$request->date_range1 . ' 00:00:00', $request->date_range2 . ' 23:59:59']);
        }

        if ($request->email_sent == 'YES') {
            $booking_processes_ids = $booking_processes_ids->where('no_invoice_sent', '>', 0);
        } elseif ($request->email_sent == 'NO') {
            $booking_processes_ids = $booking_processes_ids->where('no_invoice_sent', 0);
        }

        $booking_payment_data = array();

        if ($request->search_keyword) {
            $search_keyword = $request->search_keyword;

            $contact_ids = Contact::where(function ($query) use ($search_keyword) {
                $query->where('first_name', 'like', "%$search_keyword%");
                $query->orWhere('middle_name', 'like', "%$search_keyword%");
                $query->orWhere('last_name', 'like', "%$search_keyword%");
            })->pluck('id');

            if ($request->search_type == 'All' || $request->search_type == '') {
                $search_type = $request->search_type;

                $booking_ids = BookingProcessPaymentDetails::whereIn('customer_id', $contact_ids)->pluck('booking_process_id')->toArray();

                $booking_ids1 = BookingProcessPaymentDetails::whereIn('payi_id', $contact_ids)
                    ->pluck('booking_process_id')
                    ->toArray();

                $courseIds = Course::where('name', 'like', "%$search_keyword%")
                ->orWhere('name_en', 'like', "%$search_keyword%")
                ->pluck('id');
                $courseDetailIds = CourseDetail::whereIn('course_id', $courseIds)->pluck('id')->toArray();
                $booking_ids2 = BookingProcessPaymentDetails::whereIn('course_detail_id', $courseDetailIds)->pluck('booking_process_id');

                //dd($booking_processes_ids->orderBy('id','desc')->get()->toArray());
                if (count($courseDetailIds) > 0) {
                    $booking_processes_ids = $booking_processes_ids->whereIn('course_detail_id', $courseDetailIds);
                } elseif ($contact_ids->count() == 0) {
                    $booking_processes_ids = $booking_processes_ids->Where(function ($query) use ($search_keyword) {
                        $query->where('invoice_number', 'like', "%$search_keyword%");
                    });
                } else {
                    $booking_ids = array_intersect($booking_ids, $booking_ids1);
                    $booking_processes_ids = $booking_processes_ids->whereIn('booking_process_id', $booking_ids)
                        ->whereIn('customer_id', $contact_ids);
                }
            } elseif ($request->search_type == 'Customer') {
                $booking_processes_ids = $booking_processes_ids->whereIn('customer_id', $contact_ids);
            } elseif ($request->search_type == 'Payee') {
                $booking_processes_ids = $booking_processes_ids->whereIn('payi_id', $contact_ids);
            } elseif ($request->search_type == 'Course') {
                $courseIds = Course::where('name', 'like', "%$search_keyword%")
                ->orWhere('name_en', 'like', "%$search_keyword%")
                ->pluck('id');
                $courseDetailIds = CourseDetail::whereIn('course_id', $courseIds)->pluck('id');
                $booking_processes_ids = $booking_processes_ids->whereIn('course_detail_id', $courseDetailIds);
            } elseif ($request->search_type == 'Invoice') {
                if ($contact_ids->count() == 0) {
                    $booking_processes_ids = $booking_processes_ids->Where(function ($query) use ($search_keyword) {
                        $query->where('invoice_number', 'like', "%$search_keyword%");
                    });
                }
            }
        }

        if ($request->course_type) {
            $course_type = $request->course_type;
            $courseIds = Course::where('type', $course_type)->pluck('id');
            $courseDetailIds = CourseDetail::whereIn('course_id', $courseIds)->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('course_detail_id', $courseDetailIds);
        }
        /**Payment card type and card brand base search */
        if ($request->payment_card_search) {
            $payment_card_search = $request->payment_card_search;
            $payment_ids = BookingPayment::where(function ($query) use ($payment_card_search) {
                $query->where('payment_card_type', 'like', "%$payment_card_search%");
                $query->orWhere('payment_card_brand', 'like', "%$payment_card_search%");
            })->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('payment_id', $payment_ids);
        }

        /*** Payment Method Based Search ***/
        if ($request->payment_method_id) {
            $payment_method_id = $request->payment_method_id;
            $booking_processes_ids = $booking_processes_ids->where('payment_method_id', $payment_method_id);
        }

        /*** Payment Card Based Search ***/
        if ($request->payment_card) {
            $payment_card = $request->payment_card;
            $payment_ids = BookingPayment::where('payment_card_brand', $payment_card)->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('payment_id', $payment_ids);
        }

        $booking_processes_count = $booking_processes_ids->count();

        $booking_processes_ids = $booking_processes_ids->skip($perPage * ($page - 1))->take($perPage);

        $booking_payment_data = $booking_processes_ids->with('payment_deta')
            ->with('customer')
            ->with('payi_detail')
            ->with('lead_datails.lead_data')
            ->with('lead_datails.course_data')
            ->with('booking_customer_datails')
            ->with(['payment_detail' => function ($query) {
                $query->select('id', 'payment_number', 'payment_card_type', 'payment_card_brand', 'beleg_uuid', 'beleg_nummer');
            }])
            ->with('cancell_details')
            ->get();


        $data = [
            'booking_processes' => $booking_payment_data,
            'count' => $booking_processes_count
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /*Get customer bookings */
    public function getBookings(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
            'date_type' => 'in:Upcoming,Past,Ongoing'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $booking_processes_ids = [];

        $id = auth()->user()->contact_id;

        $contact = Contact::where('id', $id)->first();
        if ($contact) {
            if ($contact->category_id == 1) {
                $booking_processes_ids = BookingProcessCustomerDetails::where('customer_id', $id)
                    ->orWhere('payi_id', $id)
                    ->pluck('booking_process_id');
            }
        }

        $current_date = date('Y-m-d H:i:s');
        $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
            ->pluck('booking_process_id');

        if ($request->date_type == 'Upcoming') {
            $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                ->where('StartDate_Time', '>', $current_date)
                ->pluck('booking_process_id');
        }
        if ($request->date_type == 'Past') {
            $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                ->where('EndDate_Time', '<', $current_date)
                ->pluck('booking_process_id');
        }
        if ($request->date_type == 'Ongoing') {
            $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                ->where('StartDate_Time', '<=', $current_date)
                ->where('EndDate_Time', '>=', $current_date)
                ->pluck('booking_process_id');
        }

        $booking_detail = BookingProcesses::whereIn('id', $ongoing_booking_processes_ids)
            ->where('is_trash', 0)
            ->with(['course_detail.course_data', 'course_detail.course_detail_data'])
            ->with(['customer_detail' => function ($query) use ($id) {
                $query->where('customer_id', $id);
            }, 'customer_detail.customer'])
            ->with(['payment_detail' => function ($query) use ($id) {
                $query->where('customer_id', $id);
            }, 'payment_detail.customer'])
            ->with(['instructor_detail' => function ($query) use ($id) {
                $query->where('contact_id', $id);
            }, 'instructor_detail.contact'])
            ->with(['extra_participant_detail'])
            ->orderBy('id', 'desc');

        $booking_processes_count = $booking_detail->count();

        $booking_detail = $booking_detail->skip($perPage * ($page - 1))->take($perPage)
            ->get();

        $data = [
            'booking_details' => $booking_detail,
            'count' => $booking_processes_count
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /*Updating customer information */
    public function updateCustomerDetails(Request $request)
    {
        $v = $this->checkCustomerValidation($request);
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        $id = auth()->user()->contact_id;

        $checkEmail = Contact::where('id', '!=', $id)->where('email', $request->email)->count();

        if ($checkEmail) return $this->sendResponse(false, __('strings.email_already_taken'));

        if ($request->category_id === 1 && !$request->nationality) {
            return $this->sendResponse(false, __('strings.addition_persons_validation'));
        }

        $contact = Contact::find($id);
        if (!$contact) return $this->sendResponse(false, __('strings.contact_not_found'));

        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;

        if ($request->profile_pic && $request->imageUpdate && $request->is_app_user) {
            $input['profile_pic'] = $request->profile_pic;
        } elseif ($request->profile_pic && $request->imageUpdate) {
            $url = $this->uploadImage('contacts', $request->first_name, $request->profile_pic);
            $input['profile_pic'] = $url;
        }

        if ($request->category_id === 1 && $contact->QR_number == '') {
            $nine_digit_random_number = mt_rand(100000000, 999999999);
            $update_data['QR_nine_digit_random_number'] = $nine_digit_random_number;
            $input['QR_number'] = $update_data['QR_nine_digit_random_number'];
        }

        $contact->update($input);

        // if( $request->category_id === 1 && $request->display_in_app && (empty($contact->user_detail)))
        // {
        //     //create user with email and send notification
        //     $input = [];
        //     $input['email'] = $request->email;
        //     $password = Str::random(6);
        //     $input['password'] = Hash::make($password);
        //     $input['contact_id'] = $contact->id;
        //     $input['is_app_user'] = 1;
        //     $input['is_verified'] = 1;
        //     $input['email_token'] = '';
        //     $input['device_token'] = '';
        //     $input['device_type'] = '';
        //     $input['name'] =  $contact->salutation.' '.$contact->first_name.' '.$contact->last_name;
        //     $user = User::create($input);
        //     $user->notify(new SendPassword($password));
        // }

        if ($request->allergies) {
            ContactAllergy::where('contact_id', $id)->delete();
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                    'contact_id' => $contact->id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if ($request->languages) {
            ContactLanguage::where('contact_id', $id)->delete();
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                    'contact_id' => $contact->id,
                    'language_id' => $language
                ]);
            }
        }

        if ($request->address) {
            ContactAddress::where('contact_id', $id)->delete();
            foreach ($request->address as $address) {
                ContactAddress::create([
                    'contact_id' => $contact->id,
                ] + $address);
            }
        }

        if ($request->addition_persons) {
            ContactAdditionalPerson::where('contact_id', $id)->delete();
            foreach ($request->addition_persons as $person) {
                ContactAdditionalPerson::create([
                    'contact_id' => $contact->id,
                ] + $person);
            }
        }

        $user = User::where('contact_id', $id);
        if ($user) {
            $update['email'] = $request->email;
            $user->update($update);
        }
        return $this->sendResponse(true, __('strings.contact_updated_success'));
    }

    /*Updating Relative customer information */
    public function updateRelativeCustomerDetails(Request $request, $email)
    {
        $v = $this->checkCustomerValidation($request);
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        if ($email) {
            $id = Contact::where('email', $request->email)
                ->where('created_by', auth()->user()->id)->value('id');
        } else {
            return $this->sendResponse(false, __('strings.contact_not_found'));
        }

        $checkEmail = Contact::where('id', '!=', $id)->where('email', $request->email)->count();

        if ($checkEmail) return $this->sendResponse(false, __('strings.email_already_taken'));

        if ($request->category_id === 1 && !$request->nationality) {
            return $this->sendResponse(false, __('strings.addition_persons_validation'));
        }

        $contact = Contact::find($id);
        if (!$contact) return $this->sendResponse(false, __('strings.contact_not_found'));

        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;

        if ($request->profile_pic && $request->imageUpdate && $request->is_app_user) {
            $input['profile_pic'] = $request->profile_pic;
        } elseif ($request->profile_pic && $request->imageUpdate) {
            $url = $this->uploadImage('contacts', $request->first_name, $request->profile_pic);
            $input['profile_pic'] = $url;
        }

        if ($request->category_id === 1 && $contact->QR_number == '') {
            $nine_digit_random_number = mt_rand(100000000, 999999999);
            $update_data['QR_nine_digit_random_number'] = $nine_digit_random_number;
            $input['QR_number'] = $update_data['QR_nine_digit_random_number'];
        }

        $contact->update($input);

        if ($request->allergies) {
            ContactAllergy::where('contact_id', $id)->delete();
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                    'contact_id' => $contact->id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if ($request->languages) {
            ContactLanguage::where('contact_id', $id)->delete();
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                    'contact_id' => $contact->id,
                    'language_id' => $language
                ]);
            }
        }

        if ($request->address) {
            ContactAddress::where('contact_id', $id)->delete();
            foreach ($request->address as $address) {
                ContactAddress::create([
                    'contact_id' => $contact->id,
                ] + $address);
            }
        }

        if ($request->addition_persons) {
            ContactAdditionalPerson::where('contact_id', $id)->delete();
            foreach ($request->addition_persons as $person) {
                ContactAdditionalPerson::create([
                    'contact_id' => $contact->id,
                ] + $person);
            }
        }

        $user = User::where('contact_id', $id);
        if ($user) {
            $update['email'] = $request->email;
            $user->update($update);
        }
        return $this->sendResponse(true, __('strings.contact_updated_success'));
    }

    /* API for Deleting Relative Contact information */
    public function deleteRelativeCustomerDetails($email)
    {
        if ($email) {
            $id = Contact::where('email', $email)
                ->where('created_by', auth()->user()->id)->value('id');
        } else {
            return $this->sendResponse(false, __('strings.contact_not_found'));
        }

        $contact = Contact::find($id);
        if (!$contact) return $this->sendResponse(false, 'Contact not found');
        $checkUser = User::where('contact_id', $id)->count();
        if ($checkUser) return $this->sendResponse(false, __('strings.delete_contact_user_exists'));

        $contact->delete();

        return $this->sendResponse(true, __('strings.contact_deleted_success'));
    }

    /* Check validation for adding/updating contact */
    public function checkCustomerValidation($request)
    {
        $v = validator($request->all(), [
            'category_id' => 'required|integer|min:1',
            'office_id' => 'nullable|integer|min:1',
            'difficulty_level_id' => 'nullable|array',
            'salutation' => 'nullable|in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'first_name' => 'max:250',
            'middle_name' => 'nullable|max:250',
            'last_name' => 'max:250',
            'email' => 'required|email',
            'mobile1' => 'max:25',
            'mobile2' => 'max:25',
            'nationality' => 'max:50',
            'designation' => 'max:50',
            'dob' => 'date_format:Y-m-d',
            'gender' => 'in:M,F,O',
            'display_in_app' => 'boolean',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            //'allergies' => 'required_if:category_id,1',
            //'languages' => 'required_if:category_id,1',
            'allergies' => 'nullable',
            'languages' => 'nullable',

            'address' => 'array',
            'address.*.type' => 'in:L,P',
            'address.*.street_address1' => 'max:250',
            'address.*.city' => 'max:50',
            'address.*.state' => 'max:50',
            'address.*.country' => 'nullable|integer|min:1',
            'address.*.zip' => 'nullable|max:30',

            'addition_persons' => 'array',
            'addition_persons.*.salutation' => 'in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'addition_persons.*.name' => 'max:50',
            'addition_persons.*.relationaship' => 'nullable|in:Spouse,Father,Mother,Brother,Sister,Relative',
            'addition_persons.*.mobile1' => 'max:25',
            'addition_persons.*.mobile2' => 'max:25',
            'addition_persons.*.comments' => 'max:500',
        ], [
            'address.array' => 'Address must be an array',
            'address.*.type.in' => 'Address type is invalid',
            'address.*.street_address1.max' => 'Address maximum size is 250 character',
            'address.*.city.max' => 'Address city maximum size is 50 character',
            'address.*.state.max' => 'Address state maximum size is 50 character',
            'address.*.country.integer' => 'Country must be an integer',
            'address.*.country.min' => 'Country must be greater then 1',
            'address.*.zip.max' => 'Zip maximum size is 30 character',

            'addition_persons.array' => 'Addition persons must be an array',
            'addition_persons.*.salutation.in' => 'Addition persons salutation is invalid',
            'addition_persons.*.name.max' => 'Addition persons name maximum size is 50 character',
            'addition_persons.*.relationaship.in' => 'Addition persons relationaship is invalid',
            'addition_persons.*.mobile1.max' => 'Addition persons mobile1 maximum size is 15 character',
            'addition_persons.*.mobile2.max' => 'Addition persons mobile2 maximum size is 15 character',
            'addition_persons.*.comments.max' => 'Addition persons comments maximum size is 500 character',

            'addition_persons.*.relationaship.in' => "Additional contact relationship is not valid",
            'allergies.required_if' => 'Allergies field is required',
            'languages.required_if' => 'Some fields are required in Additional Details Tab',
        ]);
        return $v;
    }

    /**Get master season and daytime list */
    public function seasonDayTimeMasterList()
    {
        $season = SeasonDaytimeMaster::get();
        return $this->sendResponse(true, 'success', $season);
    }

    /** Get VAT percentage */
    public function getVat()
    {
        $vat = SequenceMaster::select('id', 'sequence as percentage')->where('code', 'VAT')->first();

        $lvat = SequenceMaster::select('id', 'sequence as percentage')->where('code', 'LVAT')->first();

        $data = [
            "VAT" => $vat,
            "LVAT" => $lvat,
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /* Create new customer */
    public function createCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'salutation' => 'nullable|in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'first_name' => 'required|max:250',
            'middle_name' => 'nullable|max:250',
            'last_name' => 'required|max:250',
            'email' => 'required|email|unique:contacts',
            'mobile1' => 'required|max:25',
            'mobile2' => 'max:25',
            'nationality' => 'nullable|max:50',
            //'designation'=>'max:50',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            'accommodation' => 'nullable',
            'dob' => 'required|date_format:Y-m-d',
            'gender' => 'required|in:M,F,O',
            //'profile_pic' => '',
            'display_in_app' => 'nullable|boolean',
            // 'allergies' => 'required|array',

            // 'languages' => 'required|array',
            'allergies' => 'nullable|array|exists:allergies,id',
            'languages' => 'nullable|array|exists:languages,id',
            'address' => 'required|array',
            'address.*.type' => 'in:L,P',
            'address.*.street_address1' => 'max:250',
            'address.*.city' => 'max:50',
            'address.*.state' => 'max:50',
            'address.*.country' => 'nullable|integer|min:1',
            'address.*.zip' => 'nullable|max:30',

            'addition_persons' => 'nullable|array',
            'addition_persons.*.salutation' => 'in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'addition_persons.*.name' => 'max:50',
            //'addition_persons.*.relationaship'=>'max:20|in:Spouse,Father,Mother,Brother,Sister,Relative',
            'addition_persons.*.relationship' => 'max:20|in:Spouse,Father,Mother,Brother,Sister,Relative,Child,Partner,Friend,Other',
            'addition_persons.*.mobile1' => 'max:25',
            'addition_persons.*.mobile2' => 'max:25',
            'addition_persons.*.comments' => 'max:500',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input = $request->all();
        $input['category_id'] = 1;
        $input['created_by'] = auth()->user()->id;
        if ($request->profile_pic) {
            $url = $this->uploadImage('contacts', $request->first_name, $request->profile_pic);
            $input['profile_pic'] = $url;
        }
        $input['QR_number'] = mt_rand(100000000, 999999999);
        $contact = Contact::create($input);
        if ($request->display_in_app) {
            //create customer as user with email send to notification
            $userData = [];
            $userData['email'] = $request->email;
            $password = Str::random(6);
            $userData['password'] = Hash::make($password);
            $userData['contact_id'] = $contact->id;
            $userData['is_app_user'] = 1;
            $userData['is_verified'] = 1;
            $userData['email_token'] = '';
            $userData['device_token'] = '';
            $userData['device_type'] = '';
            $userData['name'] =  $contact->salutation . ' ' . $contact->first_name . ' ' . $contact->last_name;
            $user = User::create($userData);
        }

        if ($request->addition_persons) {
            foreach ($request->addition_persons as $person) {
                if (!is_array($person)) {
                    $person = json_decode($person, true);
                }
                $relationship = !empty($person['relationship']) ? $person['relationship'] : '';
                ContactAdditionalPerson::create([
                    'contact_id' => $contact->id,
                    'relationaship' => $relationship
                ] + $person);
            }
        }

        if ($request->allergies) {
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                    'contact_id' => $contact->id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if ($request->languages) {
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                    'contact_id' => $contact->id,
                    'language_id' => $language
                ]);
            }
        }

        if ($request->address) {
            foreach ($request->address as $address) {
                if (!is_array($address)) {
                    $address = json_decode($address, true);
                }
                ContactAddress::create([
                    'contact_id' => $contact->id,
                ] + $address);
            }
        }

        if ($request->display_in_app) {
            // $user->notify(new SendPassword($password));
            $user->notify((new SendPassword($password))->locale($user->language_locale));
        }

        $data = [
            'id' => $contact['id'],
            'salutation' => $contact['salutation'],
            'first_name' => $contact['first_name'],
            'middle_name' => $contact['middle_name'],
            'last_name' => $contact['last_name'],
            'email' => $contact['email'],
        ];
        return $this->sendResponse(true, 'Customer created successfully', $data);
    }
}
