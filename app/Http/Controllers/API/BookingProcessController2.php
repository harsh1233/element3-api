<?php

namespace App\Http\Controllers\API;

use App\User;
use DateTime;
use App\Models\Contact;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Courses\Course;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Jobs\TwoWeekInvoicePendingReminder;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class BookingProcessController2 extends Controller
{
    use Functions;
    
    /**For assign instructor from booking qr */
    public function assignBookingFromQr(){
        if(!isset($_GET['qr'])){
            return $this->sendResponse(false, __('strings.required_validation',['name' => 'Qr']));
        }
        $booking_qr = $_GET['qr'];
        $booking = BookingProcesses::where('QR_number', $booking_qr)->first();

        if(!$booking){
            return $this->sendResponse(false, __('strings.not_found_validation',['name' => 'Booking qr']));
        }

        $contact_id = auth()->user()->contact_id;
        $booking_course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking->id)->first();

        $start_date_time = $booking_course_detail->StartDate_Time;
        $end_date_time = $booking_course_detail->EndDate_Time;
        $start_date = $booking_course_detail->start_date;
        $end_date = $booking_course_detail->end_date;
        $start_time = $booking_course_detail->start_time;
        $end_time = $booking_course_detail->end_time;
        $current_date_time = date('Y-m-d H:i:s');

        if($start_date_time < $current_date_time && $end_date_time > $current_date_time){
            $start_date = date('Y-m-d');
            $start_date_time = date('Y-m-d').' '.$start_time;
        }elseif($start_date_time < $current_date_time && $end_date_time < $current_date_time){
            return $this->sendResponse(false, __('strings.booking_invalid'));
        }

        /**For check instructor is available in booking dates or not */
        $dates_data[0]['StartDate_Time'] = $start_date_time;
        $dates_data[0]['EndDate_Time'] = $end_date_time;

        $data = $this->getAvailableInstructorListNew($dates_data);
        $booking_processes_ids_main = array_unique($data['booking_processes_ids_main']);
        $leave_contact_ids_main = array_unique($data['leave_contact_ids_main']);

        if (count($booking_processes_ids_main)) {
            $assigned_instructor_ids = BookingProcessInstructorDetails::whereIn('booking_process_id', $booking_processes_ids_main)->pluck('contact_id')->toArray();
            if (in_array($contact_id, $assigned_instructor_ids) || in_array($contact_id, $leave_contact_ids_main)) {
                return $this->sendResponse(false, __('strings.instructor_not_available_booking'));
            }
        }
        /**End */
        $instructor_detail_inputs['created_by'] = auth()->user()->id;
        $instructor_detail_inputs['booking_process_id'] = $booking->id;
        $instructor_detail_inputs['contact_id'] = $contact_id;

        $check_duplicate_insert=BookingProcessInstructorDetails::where('booking_process_id', $booking->id)->where('contact_id', $contact_id)->first();
        if (!$check_duplicate_insert) {
            BookingProcessInstructorDetails::create($instructor_detail_inputs);
        }else{
            return $this->sendResponse(false, __('strings.instructor_booking_already'));
        }

        $contact = Contact::find($contact_id);
        
        if (!$contact->isType('Instructor')) {
            return $this->sendResponse(false, __('strings.invalid_instructor'));
        }
        
        $contact_input_data['last_booking_date'] = date('Y-m-d');
        $contact->update($contact_input_data);

        //Add instructor timesheet info
        $this->InstructorTimesheetCreate(auth()->user()->id, $booking->id, $start_date, $end_date, $start_time, $end_time);

        //Add record to instructor booking map table
        $this->BookingInstructorMapCreate($contact_id, $booking->id, $start_date, $end_date, $start_time, $end_time);

        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($end_date);
        $interval = $datetime1->diff($datetime2);
        $days = $interval->format('%a');

        for($i = 0; $i <= $days; $i++){
            $this->checkInstructorBlockExist($start_date, $start_time, $end_time, $contact_id);
            $start_date = date('Y-m-d', strtotime($start_date. ' + 1 days'));
        }

        $instructor = Contact::where('id', $contact_id)->first();
        $instructor_name = $instructor->first_name." ".$instructor->last_name;
        $sender_id = 0;//For admin

        if (!$booking->is_draft) {
            $data['course_id'] = $booking_course_detail->course_id;
            $course = Course::find($data['course_id']);
            $data['course_name'] = $course->name;
            $data['booking_processes_id'] = $booking->id;

            $user = User::where('contact_id', $contact_id)->select('id', 'is_notification', 'device_token', 'device_type', 'contact_id')->first();
            if ($user) {
                $title = "Your Course have Assign New Instructor";
                $body = 'Instructor '.$instructor_name.' has been assigned to your course.';
                $receiver_id = $contact_id;

                Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>4,'message'=>$body,'booking_process_id'=>$booking->id]);

                if ($user['is_notification']) {
                    if (!empty($user['device_token'])) {
                        SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, 4, $data);
                    }
                }
            }
        }

        /**Return success response */
        return $this->sendResponse(true, __('strings.assign_instructor_success'));
    }

    /**Get invoice list if invoice end date goes with 14 days and still payment was pending */
    public function twoWeekPendingInvoiceList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_ids = DB::table('booking_process_customer_details as bcd')
        ->join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'bcd.booking_process_id')
        ->whereRaw('TIMESTAMPDIFF(DAY,bcd.end_date,NOW()) > 14')
        ->whereIn('bpd.status', ['Pending', 'Outstanding'])
        ->where('bpd.deleted_at', null)
        ->groupBy('bpd.id')
        ->pluck('bpd.id');
        
        $invoice_data = BookingProcessPaymentDetails::query();
        
        $count = count($booking_ids);
        
        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $invoice_data = $invoice_data->skip($perPage*($page-1))->take($perPage);
        }

        $invoice_data = $invoice_data->whereIn('id', $booking_ids)
        ->with('customer','payi_detail','course_detail.course_data','sub_child_detail')
        ->get();

        $data = [
            'invoice_data' => $invoice_data,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Invoice']), $data);
    }

    /**Two week pending invoice reminder */
    public function sendTwoWeekPendingInvoiceReminder(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'nullable|exists:booking_process_payment_details,id',
        ],[
            'invoice_ids.*.exists' => __('validation.id_exists'),
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        if($request->invoice_ids){
            $booking_ids = $request->invoice_ids;
        }else{
            $booking_ids = DB::table('booking_process_customer_details as bcd')
            ->join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'bcd.booking_process_id')
            ->whereRaw('TIMESTAMPDIFF(DAY,bcd.end_date,NOW()) > 14')
            ->whereIn('bpd.status', ['Pending', 'Outstanding'])
            ->where('bpd.deleted_at', null)
            ->groupBy('bpd.id')
            ->pluck('bpd.id');
        }
        TwoWeekInvoicePendingReminder::dispatch($booking_ids);

        return $this->sendResponse(true, __('strings.success', ['name' => 'Two week invoice pending reminder send']));
    }
}
