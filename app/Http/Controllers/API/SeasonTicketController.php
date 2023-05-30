<?php

namespace App\Http\Controllers\API;

use PDF;
use Mail;
use Excel;
use App\User;
use DateTime;
use Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Courses\Course;
use App\Models\SequenceMaster;
use App\Exports\SeasonTicketExport;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\Courses\CourseDetail;
use App\Models\SeasonTicketManagement;
use Illuminate\Support\Facades\Storage;
use App\Models\SubChild\SubChildContact;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class SeasonTicketController extends Controller
{
    use Functions;

    /* API for create season ticket */
    public function create(Request $request)
    {
        /**Call validation rules common function with request params */
        $v = $this->checkValidation($request);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        /**Get neccessary details */
        $ticket_details = $request->only('customer_id', 'customer_name', 'customer_mobile', 'customer_email', 'course_id', 'course_detail_id', 'start_date', 'end_date', 'start_time', 'end_time', 'total_price', 'discount', 'net_price', 'vat_percentage', 'vat_amount', 'vat_excluded_amount', 'payment_method_id', 'is_pay');

        if (!$request->customer_id) {
            /* $checkEmail = Contact::where('email',$request->customer_email)->count();
            if ($checkEmail) return $this->sendResponse(false,__('strings.email_already_taken')); */
            $customer_name = explode(" ", $request->customer_name);
            for ($i = 0; $i < count($customer_name); $i++) {
                if ($i == 0) {
                    $input_data['first_name'] = $customer_name[0];
                } elseif ($i == 1) {
                    $input_data['middle_name'] = $customer_name[1];
                } elseif ($i == 2) {
                    $input_data['last_name'] = $customer_name[2];
                }
            }
            $input_data['email'] = $request->customer_email;
            $input_data['category_id'] = 1;
            $input_data['mobile1'] = $request->customer_mobile;
            $contact = Contact::create($input_data);
            $ticket_details['customer_id'] = $contact->id;
        } else {
            $ticket_details['customer_id'] = $request->customer_id;
        }

        if (!$request->sub_child_id) {
            if($request->sub_child_firstname && $request->sub_child_lastname){
                $sub_child_data['first_name'] = $request->sub_child_firstname;
                $sub_child_data['last_name'] = $request->sub_child_lastname;
                $sub_child_data['contact_id'] = $ticket_details['customer_id'];
    
                $sub_contact = SubChildContact::create($sub_child_data);
                $ticket_details['sub_child_id'] = $sub_contact->id;
            }
        } else {
            $ticket_details['sub_child_id'] = $request->sub_child_id;
        }

        /**Get season ticket number */
        $season_ticket_number = SequenceMaster::where('code', 'ST')->first();
        /**Update season ticket number */
        $ticket_number = $season_ticket_number->sequence + 1;
        $season_ticket_number->update(['sequence' => $ticket_number]);
        $ticket_number = "ST" . date("m") . "" . date("Y") . $ticket_number;

        /**Manage add season ticket details */
        $input_details = $ticket_details;
        $input_details['ticket_number'] = $ticket_number;
        if ($ticket_details['is_pay']) {
            $input_details['payment_status'] = 'Success';
        } else {
            $input_details['payment_status'] = 'Pending';
        }
        unset($input_details['is_pay']);
        /** */

        /**Create season ticket */
        $season_ticket = SeasonTicketManagement::create($input_details);

        /**Add crm user action trail */
        if ($season_ticket) {
            $action_id = $season_ticket->id; //season ticket id
            $action_type = 'A'; //A = Add
            $module_id = 30; //module id base module table
            $module_name = "Season Ticket"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        /**return success response */
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Season ticket']));
    }

    /* API for update season ticket */
    public function update(Request $request, $id)
    {
        /**Call validation rules common function with request params */
        $v = $this->checkValidation($request);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        /**Check season ticket exist other wise error response */
        $season_ticket = SeasonTicketManagement::find($id);

        if (!$season_ticket) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Id']));
        }
        /**Get neccessary details */
        $ticket_details = $request->only('customer_id', 'customer_name', 'customer_mobile', 'customer_email', 'course_id', 'course_detail_id', 'start_date', 'end_date', 'start_time', 'end_time', 'total_price', 'discount', 'net_price', 'vat_percentage', 'vat_amount', 'vat_excluded_amount', 'payment_method_id', 'is_pay','sub_child_id');

        /**Manage add season ticket details */
        $update_details = $ticket_details;
        if ($ticket_details['is_pay']) {
            $update_details['payment_status'] = 'Success';
        } else {
            $update_details['payment_status'] = 'Pending';
        }
        unset($update_details['is_pay']);

        if (!$request->sub_child_id) {
            if($request->sub_child_firstname && $request->sub_child_lastname){
                $sub_child_data['first_name'] = $request->sub_child_firstname;
                $sub_child_data['last_name'] = $request->sub_child_lastname;
                $sub_child_data['contact_id'] = $ticket_details['customer_id'];
    
                $sub_contact = SubChildContact::create($sub_child_data);
                $update_details['sub_child_id'] = $sub_contact->id;
            }
        } else {
            $update_details['sub_child_id'] = $request->sub_child_id;
        }
        
        /**Update season ticket details */
        $season_ticket->update($update_details);

        /**Update crm user action trail */
        if ($season_ticket) {
            $action_id = $season_ticket->id; //season ticket id
            $action_type = 'U'; //U = Update
            $module_id = 30; //module id base module table
            $module_name = "Season Ticket"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        /**return success response */
        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Season ticket']));
    }

    /* API for list season ticket */
    public function list(Request $request)
    {
        /**Validation rules */
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'payment_status' => 'boolean',
            'payment_method' => 'exists:payment_methods,id',
            'start_date' => 'date|date_format:Y-m-d',
            'end_date' => 'date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        $season_tickets = SeasonTicketManagement::query();

        /**If search variable */
        if ($request->search) {
            $search = $request->search;
            $season_tickets = $season_tickets->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%$search%");
                $q->orWhere('customer_name', 'like', "%$search%");
                $q->orWhere('customer_mobile', 'like', "%$search%");
                $q->orWhere('customer_email', 'like', "%$search%");
            });
        }

        /**Payment status base filter */
        if (isset($request->payment_status)) {

            if ($request->payment_status)
                $payment_status = 'Success';
            else
                $payment_status = 'Pending';

            $season_tickets = $season_tickets->where('payment_status', $payment_status);
        }

        /**Payment method base filter */
        if ($request->payment_method) {
            $season_tickets = $season_tickets->where('payment_method_id', $request->payment_method);
        }

        /**Start date and end date base filter */
        if ($request->start_date && $request->end_date) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $season_tickets = $season_tickets->where(function ($q) use ($start_date, $end_date) {
                $q->whereBetween('start_date', [$start_date, $end_date]);
                $q->orWhereBetween('end_date', [$start_date, $end_date]);
            });
        }

        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');


        /**Type Active/Past base filter */
        if ($request->type == 'Past') {

            $season_tickets = $season_tickets->where('end_date', '<', $current_date);
            // $season_tickets = $season_tickets->where(function ($q) use ($current_date,$current_time) {
            //     $q->where('end_date', '<', $current_date);
            //     //$q->where('end_time','<', $current_time);
            // });

        }

        if ($request->type == 'Active') {

            $season_tickets = $season_tickets->where('end_date', '>', $current_date);
            // $season_tickets = $season_tickets->where(function ($q) use ($current_date,$current_time) {
            //     $q->where('end_date', '>', $current_date);
            //     //$q->where('end_time','>', $current_time);
            // });

        }

        /**IF customer use this API so filter only his/her sesons tickets */
        $user = auth()->user();
        if ($user) {
            if ($user->contact_detail) {
                if (auth()->user()->is_app_user && $user->contact_detail->category_id == 1) {
                    $customer_id = $user->contact_detail->id;
                    $season_tickets = $season_tickets->where('customer_id', $customer_id);
                }
            }
        }

        /**Count how many season and tickets */
        $season_tickets_count = $season_tickets->count();

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $season_tickets->skip($perPage * ($page - 1))->take($perPage);
        }

        /**Get details with child details */
        $season_tickets = $season_tickets->with('customer_detail', 'course', 'course_detail', 'payment_method_detail')
        ->with('sub_child_detail')
        ->orderBy('id','DESC')
        ->get();

        $data = [
            'season_tickets' => $season_tickets,
            'count' => $season_tickets_count
        ];

        /**Export list */
        if ($request->is_export) {
            return Excel::download(new SeasonTicketExport($season_tickets->toArray()), 'SeasonTicket.csv');
        }

        /**Return success response with details */
        return $this->sendResponse(true, __('strings.get_message', ['name' => 'Season ticket']), $data);
    }

    /* API for get season ticket */
    public function view($id)
    {
        /**Check season ticket exist other wise error response */
        $season_ticket = SeasonTicketManagement::find($id);

        if (!$season_ticket) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Season ticket']));
        }

        /**Get season ticket detail with child details */
        $season_ticket = SeasonTicketManagement::with('customer_detail', 'course', 'course_detail', 'payment_method_detail')
        ->with('sub_child_detail')
        ->where('id', $id)->first();

        /**Return success response with details */
        return $this->sendResponse(true, __('strings.get_message', ['name' => 'Season ticket']), $season_ticket);
    }

    /* API for convert season ticket to booking */
    public function convertSeasonTicketToBooking(Request $request)
    {
        /**Validation rules */
        $v = validator($request->all(), [
            'season_ticket_number' => 'required|exists:season_ticket_managements,ticket_number,deleted_at,NULL',
            'booking_process_id' => 'nullable|exists:booking_processes,id,deleted_at,NULL'
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());
        $ticket_number = $request->season_ticket_number;
        $contact_id = auth()->user()->contact_id;

        $current_date = date("Y-m-d");
        $current_time = date("H:i:s");

        $season_ticket = SeasonTicketManagement::whereDate('start_date', '<=', $current_date)
            ->where('ticket_number', $ticket_number)
            ->first();

        if (!$season_ticket) {
            return $this->sendResponse(false, __('strings.season_ticket_invalid'));
        }

        $check_season_ticket = SeasonTicketManagement::whereDate('scaned_at', '=', $current_date)
            ->where('ticket_number', $ticket_number)
            ->first();

        if ($check_season_ticket) {
            return $this->sendResponse(false, __('strings.ticket_already_scaned', ['type' => 'Season']));
        }

        $season_course_id = $season_ticket->course_id;

        /**Check if season ticket already applied or not if applied then error response */
        $check_ticket_exist = BookingProcessCustomerDetails::where('season_ticket_number', $ticket_number)
            ->where('customer_id', $season_ticket->customer_id)
            ->first();

        $course = Course::find($season_ticket->course_id);

        $course_detail = null;

        if ($season_ticket->course_detail_id) {
            $course_detail = CourseDetail::find($season_ticket->course_detail_id);
        }

        // if ($check_ticket_exist) {
        //     /**Check if booked days is passed away and still customer booked days are remain for attend then error response */
        //     $booking_process = BookingProcesses::find($check_ticket_exist->booking_process_id)->first();
        //     $booking_process_id = $booking_process->id;
        //     $booking_number = $booking_process->booking_number;

        //     $start_date = $season_ticket->start_date;
        //     $end_date = $season_ticket->end_date;

        //     $start_date = new DateTime($start_date);
        //     $end_date = new DateTime($end_date);

        //     $diff = Carbon::parse($start_date)->diff($end_date);
        //     $total_days = $diff->d + 1;
        //     /**+ 1 for carbon return 0 for if both(from and to) values are same */

        //     if ($total_days == $season_ticket->scaned_count) {
        //         return $this->sendResponse(false, __('strings.season_ticket_already_completed'));
        //     }

        //     if ($end_date < date('Y-m-d')) {
        //         return $this->sendResponse(false, __('strings.booking_is_expired', ['total_days' => $total_days, 'attended_days' => $season_ticket->scaned_count, 'remain_days' => $total_days - $season_ticket->scaned_count]));
        //     }
        //     /**Update season ticket base booking details */
        //     $this->updateSeasonBookingDetails($season_ticket, $course, $course_detail, $booking_process_id);
        //     $season_ticket->increment('scaned_count');
        //     $season_ticket->scaned_at = date('Y-m-d');
        //     $season_ticket->save();

        //     return $this->sendResponse(true, __('strings.season_to_booking_convert_sucess', ['booking_number' => $booking_number]));
        // }
        /** */

        if ($request->booking_process_id) {
            $booking_process_id = $request->booking_process_id;

            $instructor_valid = BookingProcessInstructorDetails::where('booking_process_id', $booking_process_id)
                ->where('contact_id', $contact_id)->count();
            if (!$instructor_valid) {
                return $this->sendResponse(false, __('strings.instructor_invalid'));
            }

            $booking_process = BookingProcesses::find($booking_process_id);

            $booking_course = BookingProcessCourseDetails::where('booking_process_id', $booking_process_id)
                ->where('start_date', '<=', $current_date)
                ->where('start_time', '<=', $season_ticket->start_time)
                ->where('end_date', '>=', $current_date)
                ->where('end_time', '>=', $season_ticket->end_time)
                ->first();

            if (!$booking_course) {
                return $this->sendResponse(false, __('strings.booking_date_range_invalid'));
            }

            /**Check customer booking cancel or not */
            $customer_cancel_booking = BookingProcessCustomerDetails::where('customer_id', $season_ticket->customer_id)
                ->where('booking_process_id', $booking_process_id)
                ->where('is_cancelled', true)
                ->count();

            if ($customer_cancel_booking)
                return $this->sendResponse(false, __('strings.customer_booking_cancelled'));

            $this->updateSeasonBookingDetails($season_ticket, $course, $course_detail, $booking_process_id);

        } else {
            $booking_input['created_by'] = auth()->user()->id;
            $booking_number = SequenceMaster::where('code', 'BN')->first();
            $booking_input['is_draft'] = true;

            if ($booking_number) {
                $booking_input['booking_number'] = $booking_number->sequence;
                $booking_input['booking_number'] = "EL" . date("m") . "" . date("Y") . $booking_number->sequence;
                $booking_number->increment('sequence');
            }
            $booking_qr_number = mt_rand(100000000, 999999999);
            $booking_input['QR_number'] = $booking_qr_number;

            $booking_process = BookingProcesses::create($booking_input);

            $current_date = date("Y-m-d");

            $course = Course::where('id', $season_course_id)->first();
            $booking_process_id = $booking_process->id;

            $course_detail_inputs['booking_process_id'] = $booking_process_id;
            $course_detail_inputs['course_type'] = $course->type;
            $course_detail_inputs['course_id'] = $season_ticket->course_id;
            $course_detail_inputs['course_detail_id'] = $season_ticket->course_detail_id;

            // $course_detail_inputs['StartDate_Time'] = $season_ticket->start_date . ' ' . $season_ticket->start_time;
            // $course_detail_inputs['EndDate_Time'] = $season_ticket->end_date . ' ' . $season_ticket->end_time;
            // $course_detail_inputs['start_date'] = $season_ticket->start_date;
            // $course_detail_inputs['end_date'] = $season_ticket->end_date;

            $course_detail_inputs['StartDate_Time'] = $current_date . ' ' . $season_ticket->start_time;
            $course_detail_inputs['EndDate_Time'] = $current_date . ' ' . $season_ticket->end_time;
            $course_detail_inputs['start_date'] = $current_date;
            $course_detail_inputs['end_date'] = $current_date;

            $course_detail_inputs['start_time'] = $season_ticket->start_time;
            $course_detail_inputs['end_time'] = $season_ticket->end_time;
            $course_detail_inputs['contact_id'] = $season_ticket->customer_id;
            $course_detail_inputs['no_of_participant'] = 1;

            $course_detail_inputs['created_by'] = auth()->user()->id;
            $booking_course_detail = BookingProcessCourseDetails::create($course_detail_inputs);

            $instructor_detail_inputs['booking_process_id'] = $booking_process_id;
            $instructor_detail_inputs['contact_id'] = $contact_id;
            $instructor_detail_inputs['created_by'] = $contact_id;
            $booking_instructor_detail = BookingProcessInstructorDetails::create($instructor_detail_inputs);

            $datetime1 = new DateTime($season_ticket->start_date);
            $datetime2 = new DateTime($season_ticket->end_date);

            $interval = $datetime1->diff($datetime2);
            $days = $interval->format('%a') + 1;
            $start_date = $current_date;
            $end_date = $current_date;
            $start_time = $season_ticket->start_time;
            $end_time = $season_ticket->end_time;

            //Add record to instructor booking map table
            $this->BookingInstructorMapCreate($contact_id, $booking_process_id, $start_date, $end_date, $start_time, $end_time);
        }

        /**Manage booking customer details */

        $current_date = date("Y-m-d");

        $booking_customer_detail = [];
        $booking_customer_detail['booking_process_id'] = $booking_process_id;
        $booking_customer_detail['customer_id'] = $season_ticket->customer_id;
        $booking_customer_detail['payi_id'] = $season_ticket->customer_id;
        $booking_customer_detail['course_detail_id'] = $season_ticket->course_detail_id;

        // $booking_customer_detail['StartDate_Time'] = $season_ticket->start_date . ' ' . $season_ticket->start_time;
        // $booking_customer_detail['EndDate_Time'] = $season_ticket->end_date . ' ' . $season_ticket->end_time;
        // $booking_customer_detail['start_date'] = $season_ticket->start_date;
        // $booking_customer_detail['end_date'] = $season_ticket->end_date;

        $booking_customer_detail['StartDate_Time'] = $current_date . ' ' . $season_ticket->start_time;
        $booking_customer_detail['EndDate_Time'] = $current_date . ' ' . $season_ticket->end_time;
        $booking_customer_detail['start_date'] = $current_date;
        $booking_customer_detail['end_date'] = $current_date;

        $booking_customer_detail['start_time'] = $season_ticket->start_time;
        $booking_customer_detail['end_time'] = $season_ticket->end_time;
        $booking_customer_detail['cal_payment_type'] = $course->cal_payment_type ? $course->cal_payment_type : null;
        $booking_customer_detail['is_include_lunch'] = $course_detail ? $course_detail->is_include_lunch ? $course_detail->is_include_lunch : 0 : 0;
        $booking_customer_detail['include_lunch_price'] = $course_detail ? $course_detail->include_lunch_price ? $course_detail->include_lunch_price : 0 : 0;

        $datetime1 = new DateTime($booking_customer_detail['StartDate_Time']);
        $datetime2 = new DateTime($booking_customer_detail['EndDate_Time']);

        $interval = $datetime2->diff($datetime1);
        $hours = $interval->format('%h');
        $days = $interval->format('%a') + 1;

        if ($booking_customer_detail['cal_payment_type'] == 'PD') {
            $booking_customer_detail['no_of_days'] = $days;
        } elseif ($booking_customer_detail['cal_payment_type'] == 'PH') {
            $booking_customer_detail['hours_per_day'] = $course_detail ? $course_detail->hours_per_day : 0;
        }
        $booking_customer_detail['is_payi'] = 'Yes';
        $booking_customer_detail['QR_number'] = $booking_process_id . $season_ticket->customer_id . mt_rand(100000, 999999);

        $booking_customer_detail['created_by'] = auth()->user()->id;
        $booking_customer_detail['season_ticket_number'] = $ticket_number;
        $booking_customer_detail['sub_child_id'] = ($season_ticket->sub_child_id?:null);

        /**Add booking customer details */
        BookingProcessCustomerDetails::create($booking_customer_detail);
        /**End */

        /**Manage booking customer details */
        $booking_payment_detail = [];
        $invoice_number = SequenceMaster::where('code', 'INV')->first();

        if ($invoice_number) {
            $invoiceNumber = $invoice_number->sequence;
            $booking_payment_detail['invoice_number'] = "INV" . date("m") . "" . date("Y") . $invoiceNumber;
            $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
        }

        //Payment Details
        $course_total_price = 0;
        $cal_payment_type = $course->cal_payment_type;

        $hours_per_day = $course_detail ? $course_detail->hours_per_day : 0;
        $total_price = $season_ticket->total_price;
        $price_per_day = $course_detail ? $course_detail->price_per_day : 0;
        $vat_percentage = $season_ticket->vat_percentage;

        $start_date = strtotime($current_date);
        $end_date = strtotime($current_date);
        $datediff = $end_date - $start_date;
        $no_days = round($datediff / (60 * 60 * 24));
        $no_days = $no_days + 1;

        if($course->type == 'Group' || $course->type == 'Private'){
            if ($cal_payment_type === 'PH') {
                $total_bookings_days = $no_days;
                $course_total_price = ($total_bookings_days * ($price_per_day * $hours_per_day));
            } else {
                $total_bookings_days = $days;
                $course_total_price = ($total_bookings_days * $price_per_day);
            }
        }else{
            $course_total_price = $course->price_per_item;
        }

        $excluding_vat_amount = 0;
        if ($course_total_price && $vat_percentage) {
            $excluding_vat_amount =
                $course_total_price / ((100 + $vat_percentage) / 100);
        }

        //vat amount calculation
        $vat_amount = 0;
        if ($course_total_price && $excluding_vat_amount) {
            $vat_amount = $course_total_price - $excluding_vat_amount;
        }

        $netPrise = $course_total_price;

        $booking_payment_detail['net_price'] = $netPrise;
        $booking_payment_detail['vat_amount'] = $vat_amount;
        $booking_payment_detail['no_of_days'] = $no_days;
        $booking_payment_detail['total_price'] = $course_total_price;
        $booking_payment_detail['vat_excluded_amount'] = $excluding_vat_amount;


        // $booking_payment_detail['no_of_days'] = $days;
        // $booking_payment_detail['vat_amount'] = $season_ticket->vat_amount;
        //$booking_payment_detail['net_price'] = $season_ticket->net_price;
        // $booking_payment_detail['total_price'] = $season_ticket->total_price;
        // $booking_payment_detail['vat_excluded_amount'] = $season_ticket->vat_excluded_amount;

        $booking_payment_detail['booking_process_id'] = $booking_process_id;
        $booking_payment_detail['payi_id'] = $season_ticket->customer_id;
        $booking_payment_detail['customer_id'] = $season_ticket->customer_id;

        /**If payment method not selected then by default 8 : On Credit */
        $booking_payment_detail['payment_method_id'] = ($season_ticket->payment_method_id ?: 8);
        
        $booking_payment_detail['course_detail_id'] = $season_ticket->course_detail_id;
        $booking_payment_detail['status'] = $season_ticket->payment_status;

        $booking_payment_detail['price_per_day'] = $course_detail ? $course_detail->price_per_day : 0;
        $booking_payment_detail['hours_per_day'] = $course_detail ? $course_detail->hours_per_day : 0;
        $booking_payment_detail['cal_payment_type'] = $course->cal_payment_type;
        $booking_payment_detail['vat_percentage'] = $season_ticket->vat_percentage;

        $booking_payment_detail['created_by'] = auth()->user()->id;
        $booking_payment_detail['season_ticket_number'] = $ticket_number;
        $booking_payment_detail['discount'] = $season_ticket->discount;
        $booking_payment_detail['outstanding_amount'] = $netPrise;
        $booking_payment_detail['sub_child_id'] = ($season_ticket->sub_child_id?:null);
        /**End */

        /**Add booking payment details */
        BookingProcessPaymentDetails::create($booking_payment_detail);
        /**Update payment invoice link */
        $this->generatePdf($booking_process_id);
        /**End */

        /**Increment scaned_count value in season ticket  */
        $season_ticket->increment('scaned_count');
        $season_ticket->scaned_at = date('Y-m-d');
        $season_ticket->save();
        /**Return success response with details */
        return $this->sendResponse(true, __('strings.season_to_booking_convert_sucess', ['booking_number' => $booking_process->booking_number]));
    }

    /* API for delete season ticket */
    public function delete($id)
    {
        /**Check season ticket exist other wise error response */
        $season_ticket = SeasonTicketManagement::find($id);

        if (!$season_ticket) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Id']));
        }
        /**Delete crm user action trail */
        if ($season_ticket) {
            $action_id = $season_ticket->id; //season ticket id
            $action_type = 'D'; //D = Update
            $module_id = 30; //module id base module table
            $module_name = "Season Ticket"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */
        $season_ticket->delete();

        /**Return success response with details */
        return $this->sendResponse(true, __('strings.delete_sucess', ['name' => 'Season ticket']));
    }

    /**Download season ticket invoice */
    public function downloadSeasonTicketInvoice($id)
    {
        dd("This API under development");

        /**Check season ticket exist other wise error response */
        $season_ticket = SeasonTicketManagement::find($id);

        if (!$season_ticket) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Id']));
        }
        $invoice_data['ticket']['estimate_number'] = $season_ticket->estimate_number;
        $invoice_data['ticket']['customer_name'] = $season_ticket->customer_name;
        $invoice_data['ticket']['customer_mobile'] = $season_ticket->customer_mobile;
        $invoice_data['ticket']['customer_email'] = $season_ticket->customer_email;
        $invoice_data['ticket']['course_name'] = $season_ticket->course->name ? $season_ticket->course->name : null;
        $invoice_data['ticket']['payment_method'] = $season_ticket->payment_method_detail->type ? $season_ticket->payment_method_detail->type : null;
        $invoice_data['ticket']['payment_status'] = $season_ticket->payment_status;
        $invoice_data['ticket']['start_date'] = $season_ticket->start_date;
        $invoice_data['ticket']['end_date'] = $season_ticket->end_date;
        $invoice_data['ticket']['start_time'] = $season_ticket->start_time;
        $invoice_data['ticket']['end_time'] = $season_ticket->end_time;
        $invoice_data['ticket']['total_price'] = $season_ticket->total_price;
        $invoice_data['ticket']['net_price'] = $season_ticket->net_price;
        $invoice_data['ticket']['vat_percentage'] = $season_ticket['vat_percentage'];
        $invoice_data['ticket']['vat_amount'] = $season_ticket['vat_amount'];
        $invoice_data['ticket']['vat_excluded_amount'] = $season_ticket['vat_excluded_amount'];

        $pdf = PDF::loadView('bookingProcess.estimate_invoice', $invoice_data);

        return $pdf->download($season_ticket->customer_name . '_EstimateInvoice.pdf');
    }

    /* Check validation for lesson */
    public function checkValidation($request)
    {
        /**Validation rules */
        $v = validator($request->all(), [
            'customer_id' => 'nullable|exists:contacts,id,category_id,1,deleted_at,NULL',
            'customer_name' => 'required|max:191',
            'customer_mobile' => 'nullable|max:25',
            'customer_email' => 'nullable|email|max:191',
            'sub_child_id' => 'nullable|integer',
            'sub_child_firstname' => 'nullable|max:191',
            'sub_child_lastname' => 'nullable|max:191',
            'course_id' => 'required|exists:courses,id,deleted_at,NULL',
            'course_detail_id' => 'nullable|exists:course_details,id',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'total_price' => 'required|numeric',
            'net_price' => 'required|numeric',
            'vat_percentage' => 'required|numeric',
            'vat_amount' => 'required|numeric',
            'vat_excluded_amount' => 'required|numeric',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'is_pay' => 'nullable|boolean'
        ]);

        return $v;
    }

    public function updateSeasonBookingDetails($season_ticket, $course, $course_detail, $booking_process_id)
    {
        $current_date = date("Y-m-d");

        /**Manage booking customer details */
        $booking_customer_detail = [];
        $booking_customer_detail['booking_process_id'] = $booking_process_id;
        $booking_customer_detail['customer_id'] = $season_ticket->customer_id;
        $booking_customer_detail['payi_id'] = $season_ticket->customer_id;
        $booking_customer_detail['course_detail_id'] = $season_ticket->course_detail_id;

        $booking_customer_detail['StartDate_Time'] = $current_date . ' ' . $season_ticket->start_time;
        $booking_customer_detail['EndDate_Time'] = $current_date . ' ' . $season_ticket->end_time;
        $booking_customer_detail['start_date'] = $current_date;
        $booking_customer_detail['end_date'] = $current_date;

        $booking_customer_detail['start_time'] = $season_ticket->start_time;
        $booking_customer_detail['end_time'] = $season_ticket->end_time;
        $booking_customer_detail['cal_payment_type'] = $course->cal_payment_type ? $course->cal_payment_type : null;
        $booking_customer_detail['is_include_lunch'] = $course_detail ? $course_detail->is_include_lunch ? $course_detail->is_include_lunch : 0 : 0;
        $booking_customer_detail['include_lunch_price'] = $course_detail ? $course_detail->include_lunch_price ? $course_detail->include_lunch_price : 0 : 0;

        $datetime1 = new DateTime($booking_customer_detail['StartDate_Time']);
        $datetime2 = new DateTime($booking_customer_detail['EndDate_Time']);

        $interval = $datetime2->diff($datetime1);
        $hours = $interval->format('%h');
        $days = $interval->format('%a') + 1;

        if ($booking_customer_detail['cal_payment_type'] == 'PD') {
            $booking_customer_detail['no_of_days'] = $days;
        } elseif ($booking_customer_detail['cal_payment_type'] == 'PH') {
            $booking_customer_detail['hours_per_day'] = $course_detail ? $course_detail->hours_per_day : 0;
        }
        $booking_customer_detail['is_payi'] = 'Yes';
        $booking_customer_detail['sub_child_id'] = ($season_ticket->sub_child_id?:null);

        /**Update booking customer details */
        BookingProcessCustomerDetails::where('booking_process_id', $booking_process_id)
            ->where('season_ticket_number', $season_ticket->ticket_number)
            ->update($booking_customer_detail);
        /**End */


        /**Manage booking customer details */

        // $checkCustomerStatus = BookingProcessCustomerDetails::where('booking_process_id', $booking_process_id)->where('customer_id', $season_ticket->customer_id)->orderBy('id', 'desc')->first();

        // if ($checkCustomerStatus) {
        //     $is_new_invoice = $checkCustomerStatus->is_new_invoice;
        // } else {
        //     $is_new_invoice = 0;
        // }

        // $updatePayment = BookingProcessPaymentDetails::where('booking_process_id', $booking_process_id)->where('customer_id', $season_ticket->customer_id)->where('is_new_invoice', $is_new_invoice)->orderBy('id', 'desc')->first();

        $course_total_price = 0;

        $cal_payment_type = $course->cal_payment_type;
        $hours_per_day = $course_detail ? $course_detail->hours_per_day : 0;
        $total_price = $season_ticket->total_price;
        $price_per_day = $course_detail ? $course_detail->price_per_day : 0;
        $vat_percentage = $season_ticket->vat_percentage;
        $discount = $season_ticket->discount;

        $start_date = strtotime($current_date);
        $end_date = strtotime($current_date);
        $datediff = $end_date - $start_date;
        $no_days = round($datediff / (60 * 60 * 24));
        $no_days = $no_days + 1;

        if($course->type == 'Group' || $course->type == 'Private'){
            if ($cal_payment_type === 'PH') {
                $total_bookings_days = $no_days;
                $course_total_price = ($total_bookings_days * ($price_per_day * $hours_per_day));
            } else {
                $total_bookings_days = $days;
                $course_total_price = ($total_bookings_days * $price_per_day);
            }
        }else{
            $course_total_price = $course->price_per_item;
        }

        /**Add settlement amount in main price */
        // if ($updatePayment->settlement_amount) {
        //     $settlement_amount = $updatePayment->settlement_amount;
        //     $course_total_price = $course_total_price + $settlement_amount;
        // }
        /**End */

        $excluding_vat_amount = 0;
        if ($course_total_price && $vat_percentage) {
            $excluding_vat_amount =
                $course_total_price / ((100 + $vat_percentage) / 100);
        }

        //vat amount calculation
        $vat_amount = 0;
        if ($course_total_price && $excluding_vat_amount) {
            $vat_amount = $course_total_price - $excluding_vat_amount;
        }

         $netPrise = $course_total_price;

         $netPrise = $course_total_price - ($total_price * ($discount / 100));

         $booking_payment_detail = [];      

        $booking_payment_detail['net_price'] = $netPrise;
        $booking_payment_detail['vat_amount'] = $vat_amount;
        $booking_payment_detail['no_of_days'] = $no_days;
        $booking_payment_detail['total_price'] = $course_total_price;
        $booking_payment_detail['vat_excluded_amount'] = $excluding_vat_amount;

        $booking_payment_detail['booking_process_id'] = $booking_process_id;
        $booking_payment_detail['payi_id'] = $season_ticket->customer_id;
        $booking_payment_detail['customer_id'] = $season_ticket->customer_id;

        /**If payment method not selected then by default 8 : On Credit */
        $booking_payment_detail['payment_method_id'] = ($season_ticket->payment_method_id ?: 8);

        $booking_payment_detail['course_detail_id'] = $season_ticket->course_detail_id;
        $booking_payment_detail['status'] = $season_ticket->payment_status;

        $booking_payment_detail['price_per_day'] = $course_detail ? $course_detail->price_per_day : 0;
        $booking_payment_detail['hours_per_day'] = $course_detail ? $course_detail->hours_per_day : 0;

        $booking_payment_detail['cal_payment_type'] = $course->cal_payment_type;

        $booking_payment_detail['vat_percentage'] = $season_ticket->vat_percentage;

        $booking_payment_detail['discount'] = $season_ticket->discount;
        $booking_payment_detail['sub_child_id'] = ($season_ticket->sub_child_id?:null);

        /**End */
        /**Add booking payment details */
        BookingProcessPaymentDetails::where('booking_process_id', $booking_process_id)
            ->where('season_ticket_number', $season_ticket->ticket_number)
            ->update($booking_payment_detail);
        /**Update payment invoice link */
        $this->generatePdf($booking_process_id);
        /**End */

        return;
    }

    /*Send email with season print ticket attachment */
    public function sendSeasonTicketEmail($id)
    {
        /**Check season ticket exist other wise error response */
        $season_ticket = SeasonTicketManagement::with('sub_child_detail')->find($id);

        if (!$season_ticket) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Season ticket']));
        }

        $data = $season_ticket;

        $pdf_data['ticket_number'] = $data->ticket_number;
        $pdf_data['customer_name'] = $data->customer_name;
        $pdf_data['sub_customer_name'] = ($data['sub_child_detail'] ? $data['sub_child_detail']['first_name'] : null);
        $pdf_data['start_date'] = $data->start_date;
        $pdf_data['end_date'] = $data->end_date;
        $pdf_data['start_time'] = $data->start_time;
        $pdf_data['end_time'] = $data->end_time;
        $pdf_data['customer_mobile'] = $data->customer_mobile;
        $pdf_data['discount'] = $data->discount;
        $pdf_data['total_price'] = $data->total_price;
        $pdf_data['net_price'] = $data->net_price;
        $pdf_data['vat_percentage'] = $data->vat_percentage;
        $pdf_data['vat_amount'] = $data->vat_amount;
        $pdf_data['vat_excluded_amount'] = $data->vat_excluded_amount;
        $pdf_data['customer_email'] = $data['customer_email'];
        $pdf_data['created_at'] = $data->created_at;
        $pdf_data['payment_status'] = $data->payment_status;
        $pdf_data['season_ticket_qr'] = $data->season_ticket_qr;
        $pdf_data['course'] = (Course::find($data->course_id) ? Course::find($data->course_id)->first()->name : '');

        $pdf = PDF::loadView('seasonTicket.season_print_ticket', $pdf_data);
        // return $pdf->download($data->customer_name.'_seaosn_print_ticket'.mt_rand(1000000000, time()).'.pdf');
        $url = 'SeasonPrintTicket/' . $data->customer_name . '_seaosn_print_ticket' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);
        $pdf = $url;

        /**Get default locale and set user language locale */
        $temp_locale = \App::getLocale();
        $user = User::where('email', $data['customer_email'])->first();
        if($user){
            \App::setLocale($user->language_locale);
        }
        /**End */

        Mail::send('seasonTicket.season_ticket_email', $pdf_data, function ($message) use ($pdf_data, $pdf) {
            $message->to($pdf_data['customer_email'], $pdf_data['customer_name'])
                ->subject(__('email_subject.season_ticket_invoice'));
            // if ($pdf_data['payment_status'] == 'Success') {
                $message->attachData(file_get_contents($pdf), "season_print_ticket.pdf");
            // }
        });
        
        /**Set default language locale */
        \App::setLocale($temp_locale);

        return $this->sendResponse(true, __('strings.send_season_ticket_invoice_sucess'));
    }

    /**Get season ticket base bookings */
    public function seasonTicketBookings(Request $request)
    {
        /**Validation rules */
        $v = validator($request->all(), [
            'season_ticket_number' => 'required|exists:season_ticket_managements,ticket_number,deleted_at,NULL',
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());


        $booking_process_ids = BookingProcessCustomerDetails::where('season_ticket_number', $request->season_ticket_number)
        ->pluck('booking_process_id');

        $booking_processes = BookingProcesses::whereIn('id', $booking_process_ids)
        ->where('is_trash', false)
        ->with(['course_detail.course_data'])
        ->with(['customer_detail.customer','customer_detail.sub_child_detail.allergies.allergy','customer_detail.sub_child_detail.languages.language'])
        ->with(['payment_detail.customer'=>function ($query) {
            $query->select('id', 'salutation', 'first_name', 'last_name');
        },'payment_detail.payi_detail'=>function ($query) {
            $query->select('id', 'salutation', 'first_name', 'last_name');
        },
        'payment_detail.sub_child_detail.allergies.allergy', 'payment_detail.sub_child_detail.languages.language'])
        ->orderBy('id', 'desc')
        ->get();

        return $this->sendResponse(true, __('strings.list_message',['name' => 'Bookings']), $booking_processes);
    }
}
